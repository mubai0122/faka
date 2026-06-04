/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
(function() {
    const modal = document.getElementById('buyModal');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mainNav = document.getElementById('mainNav');

    let currentProduct = null;

    function showLoading() {
        if (loadingOverlay) loadingOverlay.classList.add('active');
    }

    function hideLoading() {
        if (loadingOverlay) loadingOverlay.classList.remove('active');
    }

    function showMessage(msg, isError = false) {
        const div = document.createElement('div');
        div.textContent = msg;
        div.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:' + (isError ? '#ef4444' : '#10b981') + ';color:white;padding:12px 24px;border-radius:8px;z-index:3000;font-size:14px';
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }

    function openModal() {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            const form = document.getElementById('buyForm');
            if (form) form.reset();
        }
    }

    window.showBuyModal = function(productId, productName, productPrice) {
        currentProduct = {
            id: productId,
            name: productName,
            price: productPrice
        };
        const nameSpan = document.getElementById('modalProductName');
        const priceSpan = document.getElementById('modalProductPrice');
        const idInput = document.getElementById('productId');
        if (nameSpan) nameSpan.textContent = productName;
        if (priceSpan) priceSpan.textContent = '¥' + parseFloat(productPrice).toFixed(2);
        if (idInput) idInput.value = productId;
        openModal();
    };

    // 购买按钮事件
    document.querySelectorAll('.btn-buy:not(.disabled)').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = btn.dataset.price;
            if (id && name && price) window.showBuyModal(id, name, price);
        });
    });

    // 关闭弹窗
    const closeBtn = document.querySelector('.modal-close');
    const cancelBtn = document.querySelector('.modal-cancel');
    const overlay = document.querySelector('.modal-overlay');
    if (closeBtn) closeBtn.onclick = closeModal;
    if (cancelBtn) cancelBtn.onclick = closeModal;
    if (overlay) overlay.onclick = closeModal;

    // 提交购买
    const submitBtn = document.getElementById('submitBuy');
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

                const res = await fetch('/api/pay.php', {
                    method: 'POST',
                    body: formData
                });
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

    // 复制功能
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

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-copy');
        if (btn && btn.dataset.clipboard) {
            copyToClipboard(btn.dataset.clipboard);
        }
    });

    // 移动端菜单
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.onclick = () => mainNav.classList.toggle('active');
        document.addEventListener('click', (e) => {
            if (mainNav.classList.contains('active') &&
                !mainNav.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mainNav.classList.remove('active');
            }
        });
    }

    // 订单查询
    const queryForm = document.getElementById('queryForm');
    if (queryForm) {
        queryForm.onsubmit = function(e) {
            const orderNo = queryForm.querySelector('input[name="order_no"]').value.trim();
            if (!orderNo) {
                e.preventDefault();
                showMessage('请输入订单号', true);
            }
        };
    }

    // 商品详情弹窗
    const detailModal = document.getElementById('detailModal');

    window.showProductDetail = async function(productId) {
        if (!detailModal) return;
        detailModal.style.display = 'flex';
        document.getElementById('detailContent').innerHTML = '<div style="text-align:center; padding:20px;">加载中...</div>';

        try {
            const res = await fetch('/api/product_detail.php?id=' + productId);
            const data = await res.json();

            if (data.code === 1) {
                const p = data.data;
                let imageHtml = '';
                if (p.image) {
                    imageHtml = '<div style="text-align:center; margin-bottom:16px;"><img src="' + p.image + '" class="detail-modal-img"></div>';
                }
                document.getElementById('detailContent').innerHTML = imageHtml + `
                <div class="detail-info-row">
                    <span class="detail-label">商品名称：</span>
                    <span class="detail-value">${escapeHtml(p.name)}</span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-label">商品价格：</span>
                    <span class="detail-value" style="color:#4b5563; font-size:20px; font-weight:700;">¥${parseFloat(p.price).toFixed(2)}</span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-label">剩余库存：</span>
                    <span class="detail-value">${p.stock} 件</span>
                </div>
                <div class="detail-info-row">
                    <span class="detail-label">商品详情：</span>
                    <div class="detail-value" style="color:#6b7280; line-height:1.6;">${p.detail || '暂无详情'}</div>
                </div>
                <button class="btn btn-primary btn-block" style="margin-top:20px;" onclick="closeDetailModalAndBuy(${p.id})">立即购买</button>
            `;
            } else {
                document.getElementById('detailContent').innerHTML = '<div style="text-align:center; padding:20px; color:#999;">商品不存在</div>';
            }
        } catch (error) {
            document.getElementById('detailContent').innerHTML = '<div style="text-align:center; padding:20px; color:#999;">加载失败</div>';
        }
    };

    window.closeDetailModal = function() {
        if (detailModal) detailModal.style.display = 'none';
    };

    window.closeDetailModalAndBuy = function(productId) {
        closeDetailModal();
        const buyBtn = document.querySelector(`.btn-buy[data-id="${productId}"]`);
        if (buyBtn) buyBtn.click();
    };

    if (detailModal) {
        detailModal.querySelector('.modal-overlay')?.addEventListener('click', closeDetailModal);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // 联系客服复制功能
    window.copyContact = function(btn) {
        const text = btn.getAttribute('data-clipboard');
        if (!text || text === '暂无') {
            showMessage('暂无联系方式', true);
            return;
        }
        copyToClipboard(text);
    };
})();
