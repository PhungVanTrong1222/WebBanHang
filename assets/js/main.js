// ===== QUANTITY CONTROL =====
function changeQuantity(input, delta) {
    let val = parseInt(input.value) || 1;
    val += delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    input.value = val;
}

// ===== ADD TO CART (AJAX) =====
function addToCart(productId, quantity) {
    quantity = quantity || 1;
    fetch('/WebBanHang/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&product_id=' + productId + '&quantity=' + quantity
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_count;
            });
            showToast('Đã thêm vào giỏ hàng!');
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(() => {
        showToast('Có lỗi xảy ra', 'error');
    });
}

// ===== UPDATE CART QUANTITY =====
function updateCartQuantity(productId, quantity) {
    if (quantity < 1) return;
    fetch('/WebBanHang/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update&product_id=' + productId + '&quantity=' + quantity
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// ===== REMOVE FROM CART =====
function removeFromCart(productId) {
    // Tạm bỏ confirm vì có thể trình duyệt đã chặn popup này
    // if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
    fetch('/WebBanHang/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// ===== TOAST NOTIFICATION =====
function showToast(message, type) {
    type = type || 'success';
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:80px;right:20px;padding:12px 24px;border-radius:4px;color:#fff;font-size:14px;z-index:9999;animation:slideIn 0.3s;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
    toast.style.background = type === 'success' ? '#2e7d32' : '#d32f2f';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(function() { toast.remove(); }, 300);
    }, 2500);
}

// ===== FORM VALIDATION =====
function validateForm(form) {
    var valid = true;
    var inputs = form.querySelectorAll('[required]');
    inputs.forEach(function(input) {
        var error = input.parentElement.querySelector('.form-error');
        if (error) error.remove();

        if (!input.value.trim()) {
            valid = false;
            var msg = document.createElement('div');
            msg.className = 'form-error';
            msg.textContent = 'Vui lòng nhập ' + (input.previousElementSibling ? input.previousElementSibling.textContent.toLowerCase() : 'trường này');
            input.parentElement.appendChild(msg);
        }
    });
    return valid;
}

// ===== CONFIRM DELETE (ADMIN) =====
function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc chắn muốn xóa?');
}

// ===== HERO SLIDER =====
var _sliderCurrent = 0;
var _sliderTimer = null;
var _sliderTotal = 0;

function heroSliderNext() {
    _sliderCurrent = (_sliderCurrent + 1) % _sliderTotal;
    _updateSlider();
    _restartSliderTimer();
}

function heroSliderPrev() {
    _sliderCurrent = (_sliderCurrent - 1 + _sliderTotal) % _sliderTotal;
    _updateSlider();
    _restartSliderTimer();
}

function goToSlide(index) {
    _sliderCurrent = index;
    _updateSlider();
    _restartSliderTimer();
}

function _updateSlider() {
    var slides = document.getElementById('heroSlides');
    var dots = document.querySelectorAll('#sliderDots .dot');
    if (!slides) return;
    slides.style.transform = 'translateX(-' + (_sliderCurrent * 100) + '%)';
    for (var i = 0; i < dots.length; i++) {
        if (i === _sliderCurrent) {
            dots[i].classList.add('active');
        } else {
            dots[i].classList.remove('active');
        }
    }
}

function _restartSliderTimer() {
    if (_sliderTimer) clearInterval(_sliderTimer);
    _sliderTimer = setInterval(function() {
        _sliderCurrent = (_sliderCurrent + 1) % _sliderTotal;
        _updateSlider();
    }, 2000);
}

// Khởi tạo slider ngay (script ở cuối body, DOM đã sẵn sàng)
var _initSlides = document.getElementById('heroSlides');
if (_initSlides) {
    _sliderTotal = _initSlides.children.length;
    if (_sliderTotal > 0) {
        _restartSliderTimer();
    }
}

// ===== AUTO CLOSE FLASH MESSAGES =====
setTimeout(function() {
    var flashes = document.querySelectorAll('.flash');
    flashes.forEach(function(flash) {
        flash.style.opacity = '0';
        flash.style.transition = 'opacity 0.4s ease';
        setTimeout(function() {
            if (flash.parentElement) flash.parentElement.removeChild(flash);
        }, 400);
    });
}, 3000);

// ===== LOCATION PICKER MODAL =====
var _leafletAssetsLoader = null;
var _locationModal = null;
var _locationMap = null;
var _locationMarker = null;
var _activeLocationContext = null;
var _locationDraft = {
    lat: null,
    lng: null,
    address: ''
};

function setLocationMessage(status, message, type) {
    status.textContent = message;
    status.classList.remove('is-error', 'is-success');

    if (type) {
        status.classList.add(type === 'error' ? 'is-error' : 'is-success');
    }
}

function setCoordinatesText(el, lat, lng) {
    el.textContent = 'Tọa độ đã chọn: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
}

function buildGoogleMapsUrl(lat, lng) {
    return 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
}

function loadLeafletAssets() {
    if (window.L) {
        return Promise.resolve(window.L);
    }

    if (_leafletAssetsLoader) {
        return _leafletAssetsLoader;
    }

    _leafletAssetsLoader = new Promise(function(resolve, reject) {
        if (!document.getElementById('leaflet-css')) {
            var link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        var existingScript = document.getElementById('leaflet-js');
        if (existingScript) {
            existingScript.addEventListener('load', function() {
                resolve(window.L);
            });
            existingScript.addEventListener('error', function() {
                reject(new Error('leaflet-load-failed'));
            });
            return;
        }

        var script = document.createElement('script');
        script.id = 'leaflet-js';
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.onload = function() {
            resolve(window.L);
        };
        script.onerror = function() {
            reject(new Error('leaflet-load-failed'));
        };
        document.body.appendChild(script);
    });

    return _leafletAssetsLoader;
}

function initLocationInputs() {
    var blocks = document.querySelectorAll('.js-location-input');
    if (!blocks.length) return;

    blocks.forEach(function(block) {
        setupLocationBlock(block);
    });
}

function setupLocationBlock(block) {
    var button = block.querySelector('.js-get-current-location');
    var textarea = block.querySelector('.js-address-textarea');
    var status = block.querySelector('.js-location-status');
    var mapLink = block.querySelector('.js-open-map');
    var coordinatesEl = block.querySelector('.js-location-coordinates');
    var latInput = block.querySelector('.js-location-lat');
    var lngInput = block.querySelector('.js-location-lng');

    if (!button || !textarea || !status || !mapLink || !coordinatesEl || !latInput || !lngInput) return;

    var context = {
        button: button,
        textarea: textarea,
        status: status,
        mapLink: mapLink,
        coordinatesEl: coordinatesEl,
        latInput: latInput,
        lngInput: lngInput
    };

    button.addEventListener('click', function() {
        openLocationPicker(context);
    });
}

function ensureLocationModal() {
    if (_locationModal) {
        return _locationModal;
    }

    var wrapper = document.createElement('div');
    wrapper.className = 'location-modal-overlay';
    wrapper.innerHTML =
        '<div class="location-modal" role="dialog" aria-modal="true" aria-label="Chọn vị trí">' +
            '<div class="location-modal-header">' +
                '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">' +
                    '<div>' +
                        '<div class="location-modal-title">Chọn vị trí trên bản đồ</div>' +
                        '<div class="location-modal-subtitle">Ghim sẽ tự đặt vào vị trí hiện tại, bạn có thể kéo sang nơi khác rồi xác nhận.</div>' +
                    '</div>' +
                    '<button type="button" class="location-modal-close js-location-modal-close" aria-label="Đóng">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="location-modal-body">' +
                '<div class="location-modal-map js-location-modal-map"></div>' +
                '<div class="location-modal-preview">' +
                    '<strong>Địa chỉ đang chọn</strong>' +
                    '<div class="location-modal-preview-text js-location-modal-address">Đang chờ chọn vị trí...</div>' +
                    '<div class="location-modal-preview-coords js-location-modal-coords">Tọa độ: chưa có</div>' +
                '</div>' +
            '</div>' +
            '<div class="location-modal-footer">' +
                '<div class="location-status js-location-modal-status">Đang chuẩn bị bản đồ...</div>' +
                '<div style="display:flex;gap:10px;flex-wrap:wrap;">' +
                    '<button type="button" class="btn btn-secondary js-location-modal-cancel">Hủy</button>' +
                    '<button type="button" class="btn btn-primary js-location-modal-confirm">Xác nhận vị trí</button>' +
                '</div>' +
            '</div>' +
        '</div>';

    document.body.appendChild(wrapper);
    document.body.classList.add('location-modal-ready');

    wrapper.addEventListener('click', function(event) {
        if (event.target === wrapper) {
            closeLocationPicker();
        }
    });

    wrapper.querySelector('.js-location-modal-close').addEventListener('click', closeLocationPicker);
    wrapper.querySelector('.js-location-modal-cancel').addEventListener('click', closeLocationPicker);
    wrapper.querySelector('.js-location-modal-confirm').addEventListener('click', confirmLocationSelection);

    _locationModal = {
        overlay: wrapper,
        mapEl: wrapper.querySelector('.js-location-modal-map'),
        addressEl: wrapper.querySelector('.js-location-modal-address'),
        coordsEl: wrapper.querySelector('.js-location-modal-coords'),
        statusEl: wrapper.querySelector('.js-location-modal-status'),
        confirmBtn: wrapper.querySelector('.js-location-modal-confirm')
    };

    return _locationModal;
}

function openLocationPicker(context) {
    _activeLocationContext = context;
    var modal = ensureLocationModal();
    modal.overlay.classList.add('is-open');
    document.body.classList.add('location-modal-open');
    modal.confirmBtn.disabled = true;
    modal.addressEl.textContent = 'Đang tìm vị trí phù hợp...';
    modal.coordsEl.textContent = 'Tọa độ: chưa có';
    setLocationMessage(modal.statusEl, 'Đang tải bản đồ...', null);

    loadLeafletAssets()
        .then(function() {
            initializeLeafletMap();
            setLocationMessage(modal.statusEl, 'Đang lấy vị trí hiện tại để đặt ghim...', null);
            return prepareInitialDraft(context);
        })
        .then(function(position) {
            setDraftLocation(position.lat, position.lng, position.address || '', true);
            tryUseCurrentPosition();
        })
        .catch(function() {
            setLocationMessage(modal.statusEl, 'Không tải được bản đồ. Vui lòng kiểm tra kết nối mạng rồi thử lại.', 'error');
        });
}

function closeLocationPicker() {
    if (!_locationModal) return;
    _locationModal.overlay.classList.remove('is-open');
    document.body.classList.remove('location-modal-open');
    _activeLocationContext = null;
}

function initializeLeafletMap() {
    var modal = ensureLocationModal();
    if (_locationMap) {
        setTimeout(function() {
            _locationMap.invalidateSize();
        }, 50);
        return;
    }

    _locationMap = L.map(modal.mapEl, {
        zoomControl: true
    }).setView([10.7769, 106.7009], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(_locationMap);

    _locationMarker = L.marker([10.7769, 106.7009], {
        draggable: true
    }).addTo(_locationMap);

    _locationMap.on('click', function(event) {
        setDraftLocation(event.latlng.lat, event.latlng.lng, '', true);
    });

    _locationMarker.on('dragend', function() {
        var latlng = _locationMarker.getLatLng();
        setDraftLocation(latlng.lat, latlng.lng, '', true);
    });

    setTimeout(function() {
        _locationMap.invalidateSize();
    }, 50);
}

function confirmLocationSelection() {
    if (!_activeLocationContext || _locationDraft.lat === null || _locationDraft.lng === null) {
        return;
    }

    var context = _activeLocationContext;
    var address = _locationDraft.address || ('Tọa độ: ' + _locationDraft.lat.toFixed(6) + ', ' + _locationDraft.lng.toFixed(6));

    context.textarea.value = address;
    context.latInput.value = _locationDraft.lat.toFixed(6);
    context.lngInput.value = _locationDraft.lng.toFixed(6);
    context.mapLink.href = buildGoogleMapsUrl(_locationDraft.lat, _locationDraft.lng);
    context.mapLink.hidden = false;
    setCoordinatesText(context.coordinatesEl, _locationDraft.lat, _locationDraft.lng);
    setLocationMessage(context.status, 'Đã xác nhận vị trí trên bản đồ.', 'success');

    closeLocationPicker();
}

function setDraftLocation(lat, lng, address, lookupAddress) {
    var modal = ensureLocationModal();

    _locationDraft.lat = lat;
    _locationDraft.lng = lng;
    if (address) {
        _locationDraft.address = address;
    }

    _locationMarker.setLatLng([lat, lng]);
    _locationMap.setView([lat, lng], Math.max(_locationMap.getZoom(), 17));
    modal.coordsEl.textContent = 'Tọa độ: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
    modal.confirmBtn.disabled = false;

    if (address) {
        modal.addressEl.textContent = address;
        setLocationMessage(modal.statusEl, 'Bạn có thể kéo ghim sang vị trí khác rồi bấm xác nhận.', null);
        return;
    }

    modal.addressEl.textContent = 'Đang tra cứu địa chỉ...';
    setLocationMessage(modal.statusEl, 'Đang cập nhật địa chỉ từ vị trí đã chọn...', null);

    if (!lookupAddress) {
        return;
    }

    reverseGeocode(lat, lng)
        .then(function(resolvedAddress) {
            _locationDraft.address = resolvedAddress;
            modal.addressEl.textContent = resolvedAddress;
            setLocationMessage(modal.statusEl, 'Địa chỉ đã sẵn sàng. Bạn có thể xác nhận hoặc kéo ghim sang vị trí khác.', 'success');
        })
        .catch(function() {
            _locationDraft.address = '';
            modal.addressEl.textContent = 'Không tra cứu được địa chỉ chi tiết, bạn vẫn có thể xác nhận theo tọa độ.';
            setLocationMessage(modal.statusEl, 'Không tra cứu được địa chỉ chi tiết, nhưng vẫn có thể xác nhận vị trí này.', 'error');
        });
}

function tryUseCurrentPosition() {
    var modal = ensureLocationModal();

    if (!navigator.geolocation) {
        setLocationMessage(modal.statusEl, 'Trình duyệt này không hỗ trợ lấy vị trí hiện tại. Bạn vẫn có thể kéo ghim để chọn thủ công.', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            setDraftLocation(position.coords.latitude, position.coords.longitude, '', true);
            setLocationMessage(modal.statusEl, 'Đã đặt ghim vào vị trí hiện tại. Hãy xác nhận hoặc kéo ghim sang chỗ khác.', 'success');
        },
        function(error) {
            var message = 'Không thể lấy vị trí hiện tại, bạn có thể kéo ghim để chọn thủ công.';

            if (error && error.code === 1) {
                message = 'Bạn đã từ chối quyền vị trí. Hãy kéo ghim để chọn thủ công.';
            } else if (error && error.code === 2) {
                message = 'Không xác định được vị trí hiện tại. Hãy kéo ghim để chọn thủ công.';
            } else if (error && error.code === 3) {
                message = 'Hết thời gian lấy vị trí hiện tại. Hãy kéo ghim để chọn thủ công.';
            }

            setLocationMessage(modal.statusEl, message, 'error');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function prepareInitialDraft(context) {
    var lat = parseFloat(context.latInput.value);
    var lng = parseFloat(context.lngInput.value);
    var textValue = context.textarea.value.trim();

    if (!isNaN(lat) && !isNaN(lng)) {
        return Promise.resolve({
            lat: lat,
            lng: lng,
            address: textValue
        });
    }

    var coordsMatch = textValue.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
    if (coordsMatch) {
        return Promise.resolve({
            lat: parseFloat(coordsMatch[1]),
            lng: parseFloat(coordsMatch[2]),
            address: ''
        });
    }

    if (textValue) {
        return geocodeAddress(textValue)
            .then(function(result) {
                return {
                    lat: result.lat,
                    lng: result.lng,
                    address: result.address
                };
            })
            .catch(function() {
                return {
                    lat: 10.7769,
                    lng: 106.7009,
                    address: ''
                };
            });
    }

    return Promise.resolve({
        lat: 10.7769,
        lng: 106.7009,
        address: ''
    });
}

function reverseGeocode(lat, lng) {
    return fetch(
        'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
        + encodeURIComponent(lat)
        + '&lon=' + encodeURIComponent(lng)
        + '&accept-language=vi'
    )
        .then(function(response) {
            if (!response.ok) {
                throw new Error('reverse-geocode-failed');
            }
            return response.json();
        })
        .then(function(data) {
            if (data && data.display_name) {
                return data.display_name;
            }

            throw new Error('reverse-geocode-empty');
        });
}

function geocodeAddress(address) {
    return fetch(
        'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&accept-language=vi&q='
        + encodeURIComponent(address)
    )
        .then(function(response) {
            if (!response.ok) {
                throw new Error('geocode-failed');
            }
            return response.json();
        })
        .then(function(results) {
            if (results && results[0]) {
                return {
                    lat: parseFloat(results[0].lat),
                    lng: parseFloat(results[0].lon),
                    address: results[0].display_name || address
                };
            }

            throw new Error('geocode-empty');
        });
}

initLocationInputs();
