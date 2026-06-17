/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */
(function() {
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
})();
