// Fungsi utama untuk download video
async function downloadVideo() {
    const url = document.getElementById('tiktok-url').value.trim();
    const resultDiv = document.getElementById('result');
    const downloadBtn = document.getElementById('download-btn');
    const spinner = document.getElementById('spinner');
    const btnText = document.querySelector('.btn-text');
    
    // Validasi URL
    if (!url) {
        showError('Masukkan link TikTok terlebih dahulu!');
        return;
    }
    
    if (!isValidTikTokUrl(url)) {
        showError('Link TikTok tidak valid! Pastikan link dari TikTok.');
        return;
    }
    
    // Tampilkan loading
    downloadBtn.disabled = true;
    btnText.textContent = 'Memproses...';
    spinner.style.display = 'inline-block';
    resultDiv.style.display = 'none';
    
    try {
        // Kirim request ke backend
        const response = await fetch(`api/download.php?url=${encodeURIComponent(url)}`);
        const data = await response.json();
        
        if (data.error) {
            showError(data.error);
        } else {
            showResult(data);
            // Tampilkan iklan setelah download berhasil
            showAd();
        }
    } catch (error) {
        showError('Gagal menghubungkan ke server. Coba lagi nanti.');
        console.error('Error:', error);
    } finally {
        // Sembunyikan loading
        downloadBtn.disabled = false;
        btnText.textContent = 'Download';
        spinner.style.display = 'none';
    }
}

// Fungsi untuk menampilkan hasil download
function showResult(data) {
    const resultDiv = document.getElementById('result');
    
    let html = `
        <div class="video-result">
            <h3>Video Berhasil Diunduh!</h3>
            <video controls class="video-preview">
                <source src="${data.no_watermark}" type="video/mp4">
                Browser Anda tidak mendukung tag video.
            </video>
            <div class="download-options">
                <a href="${data.no_watermark}" class="download-btn" download="tiktok_no_wm.mp4">Download No WM</a>
                <a href="${data.hd}" class="download-btn hd" download="tiktok_hd.mp4">Download HD</a>
            </div>
        </div>
    `;
    
    resultDiv.innerHTML = html;
    resultDiv.style.display = 'block';
    
    // Scroll ke hasil
    resultDiv.scrollIntoView({ behavior: 'smooth' });
}

// Fungsi untuk menampilkan error
function showError(message) {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = `<div class="error">${message}</div>`;
    resultDiv.style.display = 'block';
}

// Fungsi validasi URL TikTok
function isValidTikTokUrl(url) {
    const tiktokDomains = [
        'tiktok.com',
        'vm.tiktok.com',
        'vt.tiktok.com',
        'www.tiktok.com',
        'm.tiktok.com'
    ];
    
    return tiktokDomains.some(domain => url.includes(domain));
}

// Event listener untuk tombol enter
document.getElementById('tiktok-url').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        downloadVideo();
    }
});