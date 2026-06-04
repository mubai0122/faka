/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
(function() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');
    
    function showLoading() {
        if (loadingOverlay) loadingOverlay.classList.add('active');
    }
    
    function hideLoading() {
        if (loadingOverlay) loadingOverlay.classList.remove('active');
    }
    
    function showMessage(msg, isError = false) {
        const div = document.createElement('div');
        div.textContent = msg;
        div.style.cssText = `
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: ${isError ? '#ef4444' : '#10b981'}; color: white;
            padding: 12px 24px; border-radius: 8px; z-index: 3000; font-size: 14px;
        `;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }
    
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.onclick = () => mainNav.classList.toggle('active');
        document.addEventListener('click', (e) => {
            if (mainNav.classList.contains('active') && 
                !mainNav.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mainNav.classList.remove('active');
            }
        });
    }
    
    window.showLoading = showLoading;
    window.hideLoading = hideLoading;
    window.showMessage = showMessage;
})();
