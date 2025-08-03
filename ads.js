// Fungsi untuk menampilkan iklan follow TikTok
function showAd() {
    const adContainer = document.getElementById('ad-container');
    
    // Animasi muncul
    adContainer.style.opacity = '0';
    adContainer.style.display = 'block';
    
    let opacity = 0;
    const fadeIn = setInterval(() => {
        opacity += 0.1;
        adContainer.style.opacity = opacity;
        
        if (opacity >= 1) {
            clearInterval(fadeIn);
        }
    }, 50);
    
    // Scroll ke iklan
    setTimeout(() => {
        adContainer.scrollIntoView({ behavior: 'smooth' });
    }, 500);
    
    // Simpan di localStorage bahwa user sudah melihat iklan
    localStorage.setItem('tiktokAdShown', 'true');
}

// Tampilkan iklan secara acak (50% chance)
window.addEventListener('load', function() {
    // Jika belum pernah ditampilkan atau random > 0.5
    if (!localStorage.getItem('tiktokAdShown') || Math.random() > 0.5) {
        setTimeout(showAd, 3000);
    }
});