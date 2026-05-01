<?php
require_once 'admin_header.php';

// Tổng quan nhanh cho panel phụ
$totalProducts = (int) $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$pendingOrders = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Top 5 sản phẩm bán chạy nhất theo số lượng từ đơn hoàn thành
$topProductsOverall = $conn->query("
    SELECT
        p.id AS product_id,
        p.name AS product_name,
        SUM(od.quantity) AS total_quantity
    FROM order_details od
    INNER JOIN orders o ON o.id = od.order_id
    INNER JOIN products p ON p.id = od.product_id
    WHERE o.status = 'completed'
    GROUP BY p.id, p.name
    ORDER BY total_quantity DESC, product_name ASC
    LIMIT 5
")->fetchAll();

$topProductIds = array_map('intval', array_column($topProductsOverall, 'product_id'));
$topProductNames = [];
foreach ($topProductsOverall as $product) {
    $topProductNames[(int) $product['product_id']] = $product['product_name'];
}

// Timeline đơn hoàn thành để tính KPI tổng doanh thu / số đơn
$orderTimelineRows = $conn->query("
    SELECT
        DATE(created_at) AS order_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(total_amount), 0) AS revenue
    FROM orders
    WHERE status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY order_date ASC
")->fetchAll();

// Timeline theo sản phẩm để dựng chart và insight
$productTimelineRows = $conn->query("
    SELECT
        DATE(o.created_at) AS order_date,
        p.id AS product_id,
        p.name AS product_name,
        SUM(od.quantity) AS quantity_sold,
        COALESCE(SUM(od.quantity * od.price), 0) AS revenue,
        COUNT(DISTINCT o.id) AS order_count
    FROM order_details od
    INNER JOIN orders o ON o.id = od.order_id
    INNER JOIN products p ON p.id = od.product_id
    WHERE o.status = 'completed'
    GROUP BY DATE(o.created_at), p.id, p.name
    ORDER BY order_date ASC, product_name ASC
")->fetchAll();

// Đơn hàng gần đây
$recentOrders = $conn->query("
    SELECT o.*, u.fullname AS user_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll();

$statusMap = [
    'pending' => ['Chờ xử lý', 'badge-pending'],
    'confirmed' => ['Đã xác nhận', 'badge-confirmed'],
    'shipping' => ['Đang giao', 'badge-shipping'],
    'completed' => ['Hoàn thành', 'badge-completed'],
    'cancelled' => ['Đã hủy', 'badge-cancelled'],
];
?>

<div class="admin-stats admin-stats-dynamic">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="stat-number" id="kpiRevenue">--</div>
        <div class="stat-label" id="kpiRevenueLabel">Doanh thu</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-number" id="kpiOrders">--</div>
        <div class="stat-label" id="kpiOrdersLabel">Đơn</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-cubes-stacked"></i></div>
        <div class="stat-number" id="kpiQuantity">--</div>
        <div class="stat-label" id="kpiQuantityLabel">Số lượng</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-trophy"></i></div>
        <div class="stat-number" id="kpiLeader">--</div>
        <div class="stat-label" id="kpiLeaderLabel">Dẫn đầu</div>
    </div>
</div>

<div class="dashboard-grid">
    <section class="dashboard-panel dashboard-panel-main">
        <div class="dashboard-panel-header">
            <div>
                <h3>Biểu đồ</h3>
                <span id="chartDescription" class="dashboard-chip">Tuần • Doanh thu</span>
            </div>
            <div class="dashboard-toolbar-controls dashboard-toolbar-controls-compact">
                <label class="dashboard-control">
                    <span>Chỉ số</span>
                    <select id="metricSelect">
                        <option value="revenue" selected>Doanh thu</option>
                        <option value="quantity">Số lượng</option>
                        <option value="orders">Đơn</option>
                    </select>
                </label>
                <label class="dashboard-control">
                    <span>Mốc</span>
                    <select id="groupBySelect">
                        <option value="day">Ngày</option>
                        <option value="week" selected>Tuần</option>
                        <option value="month">Tháng</option>
                        <option value="quarter">3 tháng</option>
                    </select>
                </label>
            </div>
        </div>
        <div class="dashboard-chart-wrap">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </section>

    <aside class="dashboard-panel dashboard-panel-side">
        <div class="dashboard-panel-header">
            <div>
                <h3>Tổng quan</h3>
            </div>
        </div>

        <div class="dashboard-mini-stats">
            <div class="dashboard-mini-stat">
                <span class="mini-label">Đơn chờ xử lý</span>
                <strong><?= $pendingOrders ?></strong>
            </div>
            <div class="dashboard-mini-stat">
                <span class="mini-label">Khách hàng</span>
                <strong><?= $totalUsers ?></strong>
            </div>
            <div class="dashboard-mini-stat">
                <span class="mini-label">Sản phẩm</span>
                <strong><?= $totalProducts ?></strong>
            </div>
        </div>

        <div class="dashboard-movers">
            <div class="dashboard-mover-block">
                <h4><i class="fa-solid fa-arrow-trend-up"></i> Tăng mạnh</h4>
                <div id="topGainers" class="dashboard-mover-list"></div>
            </div>
            <div class="dashboard-mover-block">
                <h4><i class="fa-solid fa-arrow-trend-down"></i> Giảm mạnh</h4>
                <div id="topDecliners" class="dashboard-mover-list"></div>
            </div>
        </div>
    </aside>
</div>

<h2 style="font-size:18px; margin:28px 0 16px;">Đơn gần đây</h2>
<table class="admin-table">
    <thead>
        <tr>
            <th>Mã ĐH</th>
            <th>Khách hàng</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="5" style="text-align:center; color:#999; padding:30px;">Chưa có đơn hàng nào</td></tr>
        <?php else: ?>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= sanitize($o['fullname']) ?></td>
            <td style="color:var(--palette-accent); font-weight:600;"><?= formatPrice($o['total_amount']) ?></td>
            <td>
                <?php $s = $statusMap[$o['status']] ?? ['Unknown', 'badge-pending']; ?>
                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const dashboardData = {
    orders: <?= json_encode($orderTimelineRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    products: <?= json_encode($productTimelineRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    topProductIds: <?= json_encode($topProductIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    topProductNames: <?= json_encode($topProductNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};

const metricConfig = {
    revenue: {
        label: 'Doanh thu',
        format: (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(value),
    },
    quantity: {
        label: 'Số lượng bán',
        format: (value) => new Intl.NumberFormat('vi-VN').format(value),
    },
    orders: {
        label: 'Số đơn chứa sản phẩm',
        format: (value) => new Intl.NumberFormat('vi-VN').format(value),
    }
};

const groupLabels = {
    day: 'ngày',
    week: 'tuần',
    month: 'tháng',
    quarter: '3 tháng'
};

const groupLimit = {
    day: 14,
    week: 12,
    month: 12,
    quarter: 8
};

const seriesColors = [
    '#14213D',
    '#E76F51',
    '#2A9D8F',
    '#F4A261',
    '#4C6FFF',
    '#94A3B8'
];

let salesTrendChart = null;

function formatNumber(value) {
    return new Intl.NumberFormat('vi-VN').format(value);
}

function startOfWeek(date) {
    const result = new Date(date);
    const day = (result.getDay() + 6) % 7;
    result.setDate(result.getDate() - day);
    result.setHours(0, 0, 0, 0);
    return result;
}

function buildBucketFromDate(dateInput, groupBy) {
    const date = new Date(dateInput);
    date.setHours(0, 0, 0, 0);

    if (groupBy === 'day') {
        const key = date.toISOString().slice(0, 10);
        return {
            key,
            label: key.split('-').reverse().slice(0, 2).join('/'),
            sortValue: date.getTime()
        };
    }

    if (groupBy === 'week') {
        const monday = startOfWeek(date);
        const key = monday.toISOString().slice(0, 10);
        return {
            key,
            label: 'Tuần ' + String(monday.getDate()).padStart(2, '0') + '/' + String(monday.getMonth() + 1).padStart(2, '0'),
            sortValue: monday.getTime()
        };
    }

    if (groupBy === 'month') {
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
        const key = firstDay.getFullYear() + '-' + String(firstDay.getMonth() + 1).padStart(2, '0');
        return {
            key,
            label: String(firstDay.getMonth() + 1).padStart(2, '0') + '/' + firstDay.getFullYear(),
            sortValue: firstDay.getTime()
        };
    }

    const quarter = Math.floor(date.getMonth() / 3) + 1;
    const quarterStartMonth = (quarter - 1) * 3;
    const firstQuarterDay = new Date(date.getFullYear(), quarterStartMonth, 1);
    return {
        key: firstQuarterDay.getFullYear() + '-Q' + quarter,
        label: 'Q' + quarter + '/' + firstQuarterDay.getFullYear(),
        sortValue: firstQuarterDay.getTime()
    };
}

function createTimeline(groupBy) {
    const totalSteps = groupLimit[groupBy];
    const timeline = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = totalSteps - 1; i >= 0; i--) {
        let datePoint = new Date(today);

        if (groupBy === 'day') {
            datePoint.setDate(today.getDate() - i);
        } else if (groupBy === 'week') {
            datePoint = startOfWeek(today);
            datePoint.setDate(datePoint.getDate() - (i * 7));
        } else if (groupBy === 'month') {
            datePoint = new Date(today.getFullYear(), today.getMonth() - i, 1);
        } else {
            const currentQuarterMonth = Math.floor(today.getMonth() / 3) * 3;
            datePoint = new Date(today.getFullYear(), currentQuarterMonth - (i * 3), 1);
        }

        timeline.push(buildBucketFromDate(datePoint, groupBy));
    }

    return timeline;
}

function aggregateOrdersByTimeline(groupBy, timeline) {
    const bucketMap = {};
    timeline.forEach((bucket) => {
        bucketMap[bucket.key] = { revenue: 0, orders: 0 };
    });

    dashboardData.orders.forEach((row) => {
        const bucket = buildBucketFromDate(row.order_date, groupBy);
        if (!bucketMap[bucket.key]) return;
        bucketMap[bucket.key].revenue += Number(row.revenue || 0);
        bucketMap[bucket.key].orders += Number(row.order_count || 0);
    });

    return bucketMap;
}

function aggregateProductsByTimeline(groupBy, timeline) {
    const validKeys = new Set(timeline.map((bucket) => bucket.key));
    const perProduct = {};

    dashboardData.products.forEach((row) => {
        const bucket = buildBucketFromDate(row.order_date, groupBy);
        if (!validKeys.has(bucket.key)) return;

        const productId = Number(row.product_id);
        if (!perProduct[productId]) {
            perProduct[productId] = {
                name: row.product_name,
                buckets: {}
            };
        }

        if (!perProduct[productId].buckets[bucket.key]) {
            perProduct[productId].buckets[bucket.key] = { revenue: 0, quantity: 0, orders: 0 };
        }

        perProduct[productId].buckets[bucket.key].revenue += Number(row.revenue || 0);
        perProduct[productId].buckets[bucket.key].quantity += Number(row.quantity_sold || 0);
        perProduct[productId].buckets[bucket.key].orders += Number(row.order_count || 0);
    });

    return perProduct;
}

function buildSeriesData(groupBy, metricKey) {
    const timeline = createTimeline(groupBy);
    const orderBuckets = aggregateOrdersByTimeline(groupBy, timeline);
    const productBuckets = aggregateProductsByTimeline(groupBy, timeline);

    const topIds = dashboardData.topProductIds;
    const labels = timeline.map((bucket) => bucket.label);
    const datasets = [];
    const totalsByProduct = {};
    let otherSeries = new Array(timeline.length).fill(0);
    let totalQuantity = 0;

    topIds.forEach((productId, index) => {
        const source = productBuckets[productId] || { name: dashboardData.topProductNames[productId] || 'Sản phẩm', buckets: {} };
        const data = timeline.map((bucket) => {
            const point = source.buckets[bucket.key] || { revenue: 0, quantity: 0, orders: 0 };
            return Number(point[metricKey] || 0);
        });

        totalsByProduct[productId] = data.reduce((sum, value) => sum + value, 0);

        datasets.push({
            label: source.name,
            data,
            borderColor: seriesColors[index],
            backgroundColor: seriesColors[index],
            borderWidth: 2.5,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5
        });
    });

    Object.keys(productBuckets).forEach((rawId) => {
        const productId = Number(rawId);
        const source = productBuckets[productId];
        const quantitySeries = timeline.map((bucket) => Number((source.buckets[bucket.key] || {}).quantity || 0));
        totalQuantity += quantitySeries.reduce((sum, value) => sum + value, 0);

        if (topIds.includes(productId)) {
            return;
        }

        timeline.forEach((bucket, index) => {
            const point = source.buckets[bucket.key] || { revenue: 0, quantity: 0, orders: 0 };
            otherSeries[index] += Number(point[metricKey] || 0);
        });
    });

    const totalRevenue = timeline.reduce((sum, bucket) => sum + Number(orderBuckets[bucket.key].revenue || 0), 0);
    const totalOrders = timeline.reduce((sum, bucket) => sum + Number(orderBuckets[bucket.key].orders || 0), 0);

    datasets.push({
        label: 'Các sản phẩm còn lại',
        data: otherSeries,
        borderColor: seriesColors[5],
        backgroundColor: seriesColors[5],
        borderWidth: 2,
        tension: 0.3,
        borderDash: [8, 6],
        pointRadius: 2,
        pointHoverRadius: 4
    });

    return {
        labels,
        timeline,
        datasets,
        totalRevenue,
        totalOrders,
        totalQuantity,
        totalsByProduct,
        productBuckets,
        topIds
    };
}

function renderChart(groupBy, metricKey) {
    const chartData = buildSeriesData(groupBy, metricKey);
    const canvas = document.getElementById('salesTrendChart');

    if (salesTrendChart) {
        salesTrendChart.destroy();
    }

    salesTrendChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets
        },
        options: {
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                        padding: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            return label + ': ' + metricConfig[metricKey].format(context.parsed.y || 0);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.16)'
                    },
                    ticks: {
                        callback: function(value) {
                            return metricConfig[metricKey].format(value);
                        }
                    },
                    title: {
                        display: true,
                        text: metricConfig[metricKey].label
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    updateDashboardMeta(chartData, groupBy, metricKey);
}

function updateDashboardMeta(chartData, groupBy, metricKey) {
    document.getElementById('chartDescription').textContent =
        capitalize(groupLabels[groupBy]) + ' • ' + metricConfig[metricKey].label;

    document.getElementById('kpiRevenue').textContent = metricConfig.revenue.format(chartData.totalRevenue);
    document.getElementById('kpiOrders').textContent = formatNumber(chartData.totalOrders);
    document.getElementById('kpiQuantity').textContent = formatNumber(chartData.totalQuantity);

    let leaderName = 'Chưa có dữ liệu';
    let leaderValue = 0;

    Object.keys(chartData.productBuckets).forEach((rawId) => {
        const productId = Number(rawId);
        const product = chartData.productBuckets[productId];
        const total = chartData.timeline.reduce((sum, bucket) => {
            const point = product.buckets[bucket.key] || { revenue: 0, quantity: 0, orders: 0 };
            return sum + Number(point[metricKey] || 0);
        }, 0);

        if (total > leaderValue) {
            leaderValue = total;
            leaderName = product.name;
        }
    });

    document.getElementById('kpiLeader').textContent = leaderName;
    document.getElementById('kpiLeaderLabel').textContent = leaderValue > 0
        ? metricConfig[metricKey].format(leaderValue)
        : 'Dẫn đầu';

    updateMovers(chartData, metricKey);
}

function updateMovers(chartData, metricKey) {
    const topGainers = [];
    const topDecliners = [];
    const lastBucket = chartData.timeline[chartData.timeline.length - 1];
    const prevBucket = chartData.timeline[chartData.timeline.length - 2];

    Object.keys(chartData.productBuckets).forEach((rawId) => {
        const product = chartData.productBuckets[Number(rawId)];
        const current = Number((product.buckets[lastBucket.key] || {})[metricKey] || 0);
        const previous = Number((product.buckets[prevBucket.key] || {})[metricKey] || 0);
        const delta = current - previous;

        if (delta === 0) return;

        const percent = previous > 0 ? (delta / previous) * 100 : (current > 0 ? 100 : 0);
        const item = {
            name: product.name,
            delta,
            percent,
            current
        };

        if (delta > 0) {
            topGainers.push(item);
        } else {
            topDecliners.push(item);
        }
    });

    topGainers.sort((a, b) => b.delta - a.delta);
    topDecliners.sort((a, b) => a.delta - b.delta);

    renderMoverList('topGainers', topGainers.slice(0, 3), metricKey, 'Không có biến động.');
    renderMoverList('topDecliners', topDecliners.slice(0, 3), metricKey, 'Không có biến động.');
}

function renderMoverList(elementId, items, metricKey, emptyMessage) {
    const container = document.getElementById(elementId);

    if (!items.length) {
        container.innerHTML = '<div class="dashboard-empty-note">' + emptyMessage + '</div>';
        return;
    }

    container.innerHTML = items.map((item) => {
        const trendClass = item.delta >= 0 ? 'up' : 'down';
        const trendPrefix = item.delta >= 0 ? '+' : '';
        return (
            '<div class="dashboard-mover-item">' +
                '<div class="dashboard-mover-name">' + item.name + '</div>' +
                '<div class="dashboard-mover-meta">' +
                    '<span class="dashboard-mover-value">' + metricConfig[metricKey].format(item.current) + '</span>' +
                    '<span class="dashboard-mover-trend ' + trendClass + '">' + trendPrefix + item.percent.toFixed(1) + '%</span>' +
                '</div>' +
            '</div>'
        );
    }).join('');
}

function capitalize(value) {
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

document.getElementById('metricSelect').addEventListener('change', function() {
    renderChart(document.getElementById('groupBySelect').value, this.value);
});

document.getElementById('groupBySelect').addEventListener('change', function() {
    renderChart(this.value, document.getElementById('metricSelect').value);
});

renderChart('week', 'revenue');
</script>

<?php require_once 'admin_footer.php'; ?>
