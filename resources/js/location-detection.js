function postJson(url, payload) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token ?? '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
    });
}

export function bootLocationDetection() {
    window.addEventListener('open-pincode-modal', () => {
        const contextPath = window.location.pathname || '/';
        window.Livewire?.dispatch('open-pincode-modal', { contextPath });
    });

    if (!('geolocation' in navigator)) {
        return;
    }

    if (sessionStorage.getItem('medca_geo_attempted') === '1') {
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            sessionStorage.setItem('medca_geo_attempted', '1');
            postJson('/location/geolocation', {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            })
                .then((res) => {
                    if (res.ok) {
                        window.location.reload();
                    }
                })
                .catch(() => {});
        },
        () => {
            sessionStorage.setItem('medca_geo_attempted', '1');
        },
        { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
    );
}
