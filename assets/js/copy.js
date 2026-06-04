/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
(function() {
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => showMessage('复制成功！'))
                .catch(() => fallbackCopy(text));
        } else {
            fallbackCopy(text);
        }
    }
    
    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showMessage('复制成功！');
    }
    
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-copy');
        if (btn && btn.dataset.clipboard) {
            copyToClipboard(btn.dataset.cliboard || btn.dataset.clipboard);
        }
    });
})();
