/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
(function() {
    const modal = document.getElementById('buyModal');
    let currentProduct = null;
    
    if (!modal) return;
    
    const closeBtn = modal.querySelector('.modal-close');
    const cancelBtn = modal.querySelector('.modal-cancel');
    const overlay = modal.querySelector('.modal-overlay');
    const submitBtn = document.getElementById('submitBuy');
    
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        const form = document.getElementById('buyForm');
        if (form) form.reset();
    }
    
    window.showBuyModal = function(productId, productName, productPrice) {
        currentProduct = { id: productId, name: productName, price: productPrice };
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalProductPrice').textContent = '¥' + parseFloat(productPrice).toFixed(2);
        document.getElementById('productId').value = productId;
        openModal();
    };
    
    if (closeBtn) closeBtn.onclick = closeModal;
    if (cancelBtn) cancelBtn.onclick = closeModal;
    if (overlay) overlay.onclick = closeModal;
    
    if (submitBtn) {
        submitBtn.onclick = async function() {
            const productId = document.getElementById('productId').value;
            const buyerName = document.querySelector('input[name="buyer_name"]').value.trim();
            const buyerEmail = document.querySelector('input[name="buyer_email"]').value.trim();
            const buyerQq = document.querySelector('input[name="buyer_qq"]').value.trim();
            const payType = document.querySelector('input[name="pay_type"]:checked')?.value;
            
            if (!buyerEmail) {
                showMessage('请填写邮箱地址', true);
                return;
            }
            if (!/^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(buyerEmail)) {
                showMessage('请填写正确的邮箱格式', true);
                return;
            }
            if (!payType) {
                showMessage('请选择支付方式', true);
                return;
            }
            
            showLoading();
            closeModal();
            
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('buyer_name', buyerName);
                formData.append('buyer_email', buyerEmail);
                formData.append('buyer_qq', buyerQq);
                formData.append('pay_type', payType);
                
                const res = await fetch('/api/pay.php', { method: 'POST', body: formData });
                const result = await res.json();
                
                if (result.code === 1) {
                    showMessage('创建订单成功，正在跳转支付...');
                    setTimeout(() => window.location.href = result.data.pay_url, 1000);
                } else {
                    showMessage(result.msg || '创建订单失败', true);
                    hideLoading();
                }
            } catch (error) {
                showMessage('网络错误，请稍后再试', true);
                hideLoading();
            }
        };
    }
})();
