<?php

/**
 * Plugin Name: Ngan Luong payment gateway for WooCommerce
 * Plugin URI: https://www.nganluong.vn/
 * Description: Plugin tích hợp NgânLượng.vn được build trên WooCommerce 3.x
 * Version: 3.1
 * Author: Nguyen Thanh - 0968381829
 * Author URI: http://www.webckk.com/
 */
ini_set('display_errors', true);
add_action('plugins_loaded', 'woocommerce_payment_nganluong_init', 0);
add_action('parse_request', array('WC_Gateway_NganLuong', 'nganluong_return_handler'));

define('URL_API', 'http://sandbox.nganluong.vn:8088/nl30/checkout.api.nganluong.post.php'); // Đường dẫn gọi api
define('RECEIVER', 'demo@nganluong.vn'); // Email tài khoản ngân lượng
define('MERCHANT_ID', '36680'); // Mã merchant kết nối
define('MERCHANT_PASS', 'matkhauketnoi'); // Mật khẩu kết nôi


function woocommerce_payment_nganluong_init()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;

    class WC_Gateway_NganLuong extends WC_Payment_Gateway
    {

        // URL checkout của nganluong.vn - Checkout URL for Ngan Luong
        private $nganluong_url;
        // Mã merchant site code
        private $merchant_site_code;
        // Mật khẩu bảo mật - Secure password
        private $secure_pass;
        // Debug parameters
        private $debug_params;
        private $debug_md5;

        function __construct()
        {
            $this->icon = @$this->settings['icon']; // Icon URL
            $this->id = 'nganluong';
            $this->method_title = 'Ngân Lượng';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->settings['title'];
            $payment_content = '<ul class="list-content"><li class="active"><label><input type="radio" value="NL" name="option_payment" selected="true">Thanh toán bằng Ví điện tử NgânLượng</label><div class="boxContent"><p> Thanh toán trực tuyến AN TOÀN và ĐƯỢC BẢO VỆ, sử dụng thẻ ngân hàng trong và ngoài nước hoặc nhiều hình thức tiện lợi khác. Được bảo hộ &amp; cấp phép bởi NGÂN HÀNG NHÀ NƯỚC, ví điện tử duy nhất được cộng đồng ƯA THÍCH NHẤT 2 năm liên tiếp, Bộ Thông tin Truyền thông trao giải thưởng Sao Khuê<br>Giao dịch. Đăng ký ví NgânLượng.vn miễn phí <a href="https://www.nganluong.vn/?portal=nganluong&amp;page=user_register" target="_blank">tại đây</a></p></div></li><li><label><input type="radio" value="ATM_ONLINE" name="option_payment">Thanh toán online bằng thẻ ngân hàng nội địa</label><div class="boxContent"><p><i><span style="color:#ff5a00;font-weight:bold;text-decoration:underline;">Lưu ý</span>: Bạn cần đăng ký Internet-Banking hoặc dịch vụ thanh toán trực tuyến tại ngân hàng trước khi thực hiện.</i></p><ul class="cardList clearfix"><li class="bank-online-methods "><label for="vcb_ck_on"><i class="BIDV" title="Ngân hàng TMCP Đầu tư &amp; Phát triển Việt Nam"></i><input type="radio" value="BIDV" name="bankcode"></label></li><li class="bank-online-methods "><label for="vcb_ck_on"><i class="VCB" title="Ngân hàng TMCP Ngoại Thương Việt Nam"></i><input type="radio" value="VCB" name="bankcode"></label></li><li class="bank-online-methods "><label for="vnbc_ck_on"><i class="DAB" title="Ngân hàng Đông Á"></i><input type="radio" value="DAB" name="bankcode"></label></li><li class="bank-online-methods "><label for="tcb_ck_on"><i class="TCB" title="Ngân hàng Kỹ Thương"></i><input type="radio" value="TCB" name="bankcode"></label></li><li class="bank-online-methods "><label for="sml_atm_mb_ck_on"><i class="MB" title="Ngân hàng Quân Đội"></i><input type="radio" value="MB" name="bankcode"></label></li><li class="bank-online-methods "><label for="sml_atm_vib_ck_on"><i class="VIB" title="Ngân hàng Quốc tế"></i> <input type="radio" value="VIB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vtb_ck_on"> <i class="ICB" title="Ngân hàng Công Thương Việt Nam"></i> <input type="radio" value="ICB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_exb_ck_on"> <i class="EXB" title="Ngân hàng Xuất Nhập Khẩu"></i> <input type="radio" value="EXB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_acb_ck_on"> <i class="ACB" title="Ngân hàng Á Châu"></i> <input type="radio" value="ACB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_hdb_ck_on"> <i class="HDB" title="Ngân hàng Phát triển Nhà TPHCM"></i> <input type="radio" value="HDB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_msb_ck_on"> <i class="MSB" title="Ngân hàng Hàng Hải"></i> <input type="radio" value="MSB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_nvb_ck_on"> <i class="NVB" title="Ngân hàng Nam Việt"></i> <input type="radio" value="NVB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vab_ck_on"> <i class="VAB" title="Ngân hàng Việt Á"></i> <input type="radio" value="VAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vpb_ck_on"> <i class="VPB" title="Ngân Hàng Việt Nam Thịnh Vượng"></i> <input type="radio" value="VPB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_scb_ck_on"> <i class="SCB" title="Ngân hàng Sài Gòn Thương tín"></i> <input type="radio" value="SCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_pgb_ck_on"> <i class="PGB" title="Ngân hàng Xăng dầu Petrolimex"></i> <input type="radio" value="PGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_gpb_ck_on"> <i class="GPB" title="Ngân hàng TMCP Dầu khí Toàn Cầu"></i> <input type="radio" value="GPB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_agb_ck_on"> <i class="AGB" title="Ngân hàng Nông nghiệp &amp; Phát triển nông thôn"></i> <input type="radio" value="AGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_sgb_ck_on"> <i class="SGB" title="Ngân hàng Sài Gòn Công Thương"></i> <input type="radio" value="SGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="BAB" title="Ngân hàng Bắc Á"></i> <input type="radio" value="BAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="TPB" title="Tền phong bank"></i> <input type="radio" value="TPB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="NAB" title="Ngân hàng Nam Á"></i> <input type="radio" value="NAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="SHB" title="Ngân hàng TMCP Sài Gòn - Hà Nội (SHB)"></i> <input type="radio" value="SHB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="OJB" title="Ngân hàng TMCP Đại Dương (OceanBank)"></i> <input type="radio" value="OJB" name="bankcode"> </label></li> </ul> </div> </li> <li> <label><input type="radio" value="IB_ONLINE" name="option_payment">Thanh toán bằng IB</label> <div class="boxContent"> <p><i> <span style="color:#ff5a00;font-weight:bold;text-decoration:underline;">Lưu ý</span>: Bạn cần đăng ký Internet-Banking hoặc dịch vụ thanh toán trực tuyến tại ngân hàng trước khi thực hiện.</i></p> <ul class="cardList clearfix"> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="BIDV" title="Ngân hàng TMCP Đầu tư &amp; Phát triển Việt Nam"></i> <input type="radio" value="BIDV" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="VCB" title="Ngân hàng TMCP Ngoại Thương Việt Nam"></i> <input type="radio" value="VCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vnbc_ck_on"> <i class="DAB" title="Ngân hàng Đông Á"></i> <input type="radio" value="DAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="tcb_ck_on"> <i class="TCB" title="Ngân hàng Kỹ Thương"></i> <input type="radio" value="TCB" name="bankcode"> </label></li> </ul> </div> </li> <li> <label><input type="radio" value="ATM_OFFLINE" name="option_payment">Thanh toán atm offline</label> <div class="boxContent"> <ul class="cardList clearfix"> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="BIDV" title="Ngân hàng TMCP Đầu tư &amp; Phát triển Việt Nam"></i> <input type="radio" value="BIDV" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="VCB" title="Ngân hàng TMCP Ngoại Thương Việt Nam"></i> <input type="radio" value="VCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vnbc_ck_on"> <i class="DAB" title="Ngân hàng Đông Á"></i> <input type="radio" value="DAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="tcb_ck_on"> <i class="TCB" title="Ngân hàng Kỹ Thương"></i> <input type="radio" value="TCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_mb_ck_on"> <i class="MB" title="Ngân hàng Quân Đội"></i> <input type="radio" value="MB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vtb_ck_on"> <i class="ICB" title="Ngân hàng Công Thương Việt Nam"></i> <input type="radio" value="ICB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_acb_ck_on"> <i class="ACB" title="Ngân hàng Á Châu"></i> <input type="radio" value="ACB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_msb_ck_on"> <i class="MSB" title="Ngân hàng Hàng Hải"></i> <input type="radio" value="MSB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_scb_ck_on"> <i class="SCB" title="Ngân hàng Sài Gòn Thương tín"></i> <input type="radio" value="SCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_pgb_ck_on"> <i class="PGB" title="Ngân hàng Xăng dầu Petrolimex"></i> <input type="radio" value="PGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_agb_ck_on"> <i class="AGB" title="Ngân hàng Nông nghiệp &amp; Phát triển nông thôn"></i> <input type="radio" value="AGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="SHB" title="Ngân hàng TMCP Sài Gòn - Hà Nội (SHB)"></i> <input type="radio" value="SHB" name="bankcode"> </label></li> </ul> </div> </li> <li> <label><input type="radio" value="NH_OFFLINE" name="option_payment">Thanh toán tại văn phòng ngân hàng</label> <div class="boxContent"> <ul class="cardList clearfix"> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="BIDV" title="Ngân hàng TMCP Đầu tư &amp; Phát triển Việt Nam"></i> <input type="radio" value="BIDV" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vcb_ck_on"> <i class="VCB" title="Ngân hàng TMCP Ngoại Thương Việt Nam"></i> <input type="radio" value="VCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vnbc_ck_on"> <i class="DAB" title="Ngân hàng Đông Á"></i> <input type="radio" value="DAB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="tcb_ck_on"> <i class="TCB" title="Ngân hàng Kỹ Thương"></i> <input type="radio" value="TCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_mb_ck_on"> <i class="MB" title="Ngân hàng Quân Đội"></i> <input type="radio" value="MB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vib_ck_on"> <i class="VIB" title="Ngân hàng Quốc tế"></i> <input type="radio" value="VIB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_vtb_ck_on"> <i class="ICB" title="Ngân hàng Công Thương Việt Nam"></i> <input type="radio" value="ICB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_acb_ck_on"> <i class="ACB" title="Ngân hàng Á Châu"></i> <input type="radio" value="ACB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_msb_ck_on"> <i class="MSB" title="Ngân hàng Hàng Hải"></i> <input type="radio" value="MSB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_scb_ck_on"> <i class="SCB" title="Ngân hàng Sài Gòn Thương tín"></i> <input type="radio" value="SCB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_pgb_ck_on"> <i class="PGB" title="Ngân hàng Xăng dầu Petrolimex"></i> <input type="radio" value="PGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="bnt_atm_agb_ck_on"> <i class="AGB" title="Ngân hàng Nông nghiệp &amp; Phát triển nông thôn"></i> <input type="radio" value="AGB" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="sml_atm_bab_ck_on"> <i class="TPB" title="Tền phong bank"></i> <input type="radio" value="TPB" name="bankcode"> </label></li> </ul> </div> </li> <li> <label><input type="radio" value="VISA" name="option_payment" selected="true">Thanh toán bằng thẻ Visa hoặc MasterCard</label> <div class="boxContent"> <p><span style="color:#ff5a00;font-weight:bold;text-decoration:underline;">Lưu ý</span>:Visa hoặc MasterCard.</p> <ul class="cardList clearfix"> <li class="bank-online-methods "> <label for="vcb_ck_on"> Visa: <input type="radio" value="VISA" name="bankcode"> </label></li> <li class="bank-online-methods "> <label for="vnbc_ck_on"> Master:<input type="radio" value="MASTER" name="bankcode"> </label></li> </ul> </div> </li> <li> <label><input type="radio" value="CREDIT_CARD_PREPAID" name="option_payment" selected="true">Thanh toán bằng thẻ Visa hoặc MasterCard trả trước</label> </li> </ul>';
            $payment_css = '<style> ul.bankList { clear: both; height: 202px; width: 636px; } ul.bankList li { list-style-position: outside; list-style-type: none; cursor: pointer; float: left; margin-right: 0; padding: 5px 2px; text-align: center; width: 90px; } .list-content li { list-style: none outside none; margin: 0 0 10px; } .list-content li .boxContent { display: none; width: 636px; border:1px solid #cccccc; padding:10px; } .list-content li.active .boxContent { display: block; } .list-content li .boxContent ul { height:280px; } i.VISA, i.MASTE, i.AMREX, i.JCB, i.VCB, i.TCB, i.MB, i.VIB, i.ICB, i.EXB, i.ACB, i.HDB, i.MSB, i.NVB, i.DAB, i.SHB, i.OJB, i.SEA, i.TPB, i.PGB, i.BIDV, i.AGB, i.SCB, i.VPB, i.VAB, i.GPB, i.SGB,i.NAB,i.BAB { width:80px; height:30px; display:block; background:url(https://www.nganluong.vn/webskins/skins/nganluong/checkout/version3/images/bank_logo.png) no-repeat;} i.MASTE { background-position:0px -31px} i.AMREX { background-position:0px -62px} i.JCB { background-position:0px -93px;} i.VCB { background-position:0px -124px;} i.TCB { background-position:0px -155px;} i.MB { background-position:0px -186px;} i.VIB { background-position:0px -217px;} i.ICB { background-position:0px -248px;} i.EXB { background-position:0px -279px;} i.ACB { background-position:0px -310px;} i.HDB { background-position:0px -341px;} i.MSB { background-position:0px -372px;} i.NVB { background-position:0px -403px;} i.DAB { background-position:0px -434px;} i.SHB { background-position:0px -465px;} i.OJB { background-position:0px -496px;} i.SEA { background-position:0px -527px;} i.TPB { background-position:0px -558px;} i.PGB { background-position:0px -589px;} i.BIDV { background-position:0px -620px;} i.AGB { background-position:0px -651px;} i.SCB { background-position:0px -682px;} i.VPB { background-position:0px -713px;} i.VAB { background-position:0px -744px;} i.GPB { background-position:0px -775px;} i.SGB { background-position:0px -806px;} i.NAB { background-position:0px -837px;} i.BAB { background-position:0px -868px;} ul.cardList li { cursor: pointer; float: left; margin-right: 0; padding: 5px 4px; text-align: center; width: 90px; } </style>';
            $payment_js = '<script src="https://code.jquery.com/jquery-1.12.4.js" type="text/javascript" charset="utf-8"></script><script language="javascript"> $(\'input[name="option_payment"]\').bind(\'click\', function() { $(\'.list-content li\').removeClass(\'active\'); $(this).parent().parent(\'li\').addClass(\'active\'); }); </script>';
            $this->description = $payment_css.$payment_content.$payment_js;
            $this->nganluong_url = $this->settings['nganluong_url'];
            $this->merchant_site_code = $this->settings['merchant_site_code'];
            $this->merchant_id = $this->settings['merchant_id'];
            $this->secure_pass = $this->settings['secure_pass'];
            $this->redirect_page_id = $this->settings['redirect_page_id'];

            $this->debug = @$this->settings['debug'];
            $this->order_button_text = __('Proceed to Ngân Lượng', 'woocommerce');

            $this->msg['message'] = "";
            $this->msg['class'] = "";
            // Add the page after checkout to redirect to Ngan Luong
            add_action('woocommerce_receipt_NganLuong', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // add_action('woocommerce_thankyou_NganLuongVN', array($this, 'thankyou_page'));
        }

        /**
         * Logging method.
         * @param string $message
         */
        public static function log($message)
        {
            $log = new WC_Logger();
            $log->add('nganluong', $message);
        }

        public function init_form_fields()
        {
            // Admin fields
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Activate', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Activate the payment gateway for Ngan Luong', 'woocommerce'),
                    'default' => 'yes'),
                'title' => array(
                    'title' => __('Name', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Tên phương thức thanh toán ( khi khách hàng chọn phương thức thanh toán )', 'woocommerce'),
                    'default' => __('NganLuongVN', 'woocommerce')),
                'icon' => array(
                    'title' => __('Icon', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Icon phương thức thanh toán', 'woocommerce'),
                    'default' => __('https://www.nganluong.vn/css/checkout/version20/images/logoNL.png', 'woocommerce')),
                'description' => array(
                    'title' => __('Mô tả', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Mô tả phương thức thanh toán.', 'woocommerce'),
                    'default' => __('Click place order and you will be directed to the Ngan Luong website in order to make payment', 'woocommerce')),
                'merchant_id' => array(
                    'title' => __('NganLuong.vn email address', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Đây là tài khoản NganLuong.vn (Email) để nhận tiền')),
                'redirect_page_id' => array(
                    'title' => __('Return URL'),
                    'type' => 'select',
                    'options' => $this->get_pages('Hãy chọn...'),
                    'description' => __('Hãy chọn trang/url để chuyển đến sau khi khách hàng đã thanh toán tại NganLuong.vn thành công', 'woocommerce')
                ),
                'status_order' => array(
                    'title' => __('Trạng thái Order'),
                    'type' => 'select',
                    'options' => wc_get_order_statuses(),
                    'description' => __('Chọn trạng thái orders cập nhật', 'woocommerce')
                ),
                'nlcurrency' => array(
                    'title' => __('Currency', 'woocommerce'),
                    'type' => 'text',
                    'default' => 'vnd',
                    'description' => __('"vnd" or "usd"', 'woocommerce')
                ),
                'nganluong_url' => array(
                    'title' => __('Ngan Luong URL', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('"https://www.nganluong.vn/checkout.php"', 'woocommerce')
                ),
                'merchant_site_code' => array(
                    'title' => __('Merchant Site Code', 'woocommerce'),
                    'type' => 'text'
                ),
                'secure_pass' => array(
                    'title' => __('Secure Password', 'woocommerce'),
                    'type' => 'password'
                ),
            );
        }

        /**
         *  There are no payment fields for NganLuongVN, but we want to show the description if set.
         * */
        function payment_fields()
        {
            if ($this->description)
                echo wpautop(wptexturize(__($this->description, 'woocommerce')));
        }

        /**
         * Process the payment and return the result.
         * @param  int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {

            $order = wc_get_order($order_id);
            $checkouturl = $this->generate_NganLuongVN_url($order_id);
            $this->log($checkouturl);
            //  echo $checkouturl;
            // die();
            return array(
                'result' => 'success',
                'redirect' => $checkouturl
            );
        }

        function generate_NganLuongVN_url($order_id)
        {
            // This is from the class provided by Ngan Luong. Not advisable to mess.
            global $woocommerce;
            $order = new WC_Order($order_id);

            $order_items = $order->get_items();

            // Dùng tạm return_url
            $return_url = get_site_url() . '/nganluong_return?order_id=' . $order_id;
            //$return_url = 'nganluong.vn';
            $receiver = $this->merchant_id;
            $currency = $this->settings['nlcurrency'];
            $transaction_info = ''; // urlencode("Order#".$order_id." | ".$_SERVER['SERVER_NAME']);

            $order_description = $order_id;

            $order_quantity = $order->get_item_count();
            //$discount = $order->get_cart_discount();
            $discount = 0;
            //$tax = $order->get_cart_tax();
            $tax = 0;
            $fee_shipping = $order->get_total_shipping_refunded();
            $product_names = [];
            foreach ($order_items as $order_item) {
                $product_names[] = $order_item['name'];
            }
            $order_description = implode(', ', $product_names); // this goes into transaction info, which shows up on Ngan Luong as the description of goods

            $price = $order->get_total() - ($tax + $fee_shipping);
            $total_amount = $price;

            if (!empty($array_items)) {
                $array_items[0] = [
                    'item_name1' => 'Product name',
                    'item_quantity1' => 1,
                    'item_amount1' => $total_amount,
                    'item_url1' => 'http://nganluong.vn/'
                ];
            } else {
                $array_items = [];
            }
//            $payment_method = $_POST['option_payment'];

//            (isset($_POST['payment_method'])) ? ($payment_method = $_POST['payment_method']) : ($payment_method = get_post_meta($order->get_id(), '_payment_method', true)); // Lưu ý $order->id
//            echo "<pre>";var_dump($_POST);echo "</pre>";exit();
            $payment_method = $_POST['option_payment'];
//            $payment_method = $order->get_payment_method();
            $bank_code = @$_POST['bankcode'];
            $order_code = "macode_" . time();
            $payment_type = '';
            $discount_amount = 0;
            $tax_amount = 0;
            // Dùng tạm return_url
//            $return_url = get_site_url() . '/nganluong_return?order_id=' . $order_id;
            $cancel_url = urlencode('http://localhost/nganluong.vn/checkoutv3?orderid=' . $order_code);

//            $buyer_fullname = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $buyer_fullname = $order->get_formatted_billing_full_name();
            $buyer_email = $order->get_billing_email();
            $buyer_mobile = $order->get_billing_phone();
            $buyer_address = $order->get_formatted_billing_address();

//            $checkouturl = $this->buildCheckoutUrlExpand($return_url, $receiver, $transaction_info, $order_id, $price, $currency, $quantity = 1, $tax, $discount, $fee_cal = 0, $fee_shipping, $order_description);
            $nlcheckout = new NL_CheckOutV3(MERCHANT_ID, MERCHANT_PASS, RECEIVER, URL_API);

            if ($payment_method != '' && $buyer_email != "" && $buyer_mobile != "" && $buyer_fullname != "" && filter_var($buyer_email, FILTER_VALIDATE_EMAIL)) {
                $nl_result = '';
                if ($payment_method == "VISA") {
                    $nl_result = $nlcheckout->VisaCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount,
                        $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                        $buyer_address, $array_items, $bank_code);
                } elseif ($payment_method == "NL") {
//                    echo "<pre>";var_dump($payment_method);echo "</pre>";exit();
                    $nl_result = $nlcheckout->NLCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount,
                        $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                        $buyer_address, $array_items);
                } elseif ($payment_method == "ATM_ONLINE" && $bank_code != '') {
                    $nl_result = $nlcheckout->BankCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount,
                        $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                        $buyer_address, $array_items);
                } elseif ($payment_method == "NH_OFFLINE") {
                    $nl_result = $nlcheckout->officeBankCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items);
                } elseif ($payment_method == "ATM_OFFLINE") {
                    $nl_result = $nlcheckout->BankOfflineCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items);
                } elseif ($payment_method == "IB_ONLINE") {
                    $nl_result = $nlcheckout->IBCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items);
                } elseif ($payment_method == "CREDIT_CARD_PREPAID") {
                    $nl_result = $nlcheckout->PrepaidVisaCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items, $bank_code);
                }
//                echo "<pre>";var_dump((string)$nl_result->checkout_url);echo "</pre>";exit();
//                foreach ($nl_result as $t){
//                    echo "<pre>";var_dump($t);echo "</pre>";
//                }
//                die;
                if (!empty($nl_result) && (string)$nl_result->error_code == '00') {
                    //Cập nhât order với token  $nl_result->token để sử dụng check hoàn thành sau này
//                    echo "<pre>";var_dump($nl_result->checkout_url);echo "</pre>";exit();
                    $new_order_status = 'wc-processing';
                    $order->update_status($new_order_status);
                    return (string)$nl_result->checkout_url;
                } else {
                    echo $nl_result->error_message;
                }
            } else {
                echo "<h3> Bạn chưa nhập đủ thông tin khách hàng </h3>";
            }


        }

        function showMessage($content)
        {
            return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
        }

        // get all pages
        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title)
                $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }

        public
        function buildCheckoutUrl($return_url, $receiver, $transaction_info, $order_code, $price)
        {
            // This is from the class provided by Ngan Luong. Not advisable to mess.
            // This one is for simple checkout
            // Mảng các tham số chuyển tới nganluong.vn
            $arr_param = array(
                'merchant_site_code' => strval($this->merchant_site_code),
                'return_url' => strtolower(urlencode($return_url)),
                'receiver' => strval($receiver),
                'transaction_info' => strval($transaction_info),
                'order_code' => strval($order_code),
                'price' => strval($price)
            );
            $secure_code = '';
            $secure_code = implode(' ', $arr_param) . ' ' . $this->secure_pass;
            $this->debug_params = $secure_code;
            $arr_param['secure_code'] = md5($secure_code);
            $this->debug_md5 = $arr_param['secure_code'];

            /* Bước 2. Kiểm tra  biến $redirect_url xem có '?' không, nếu không có thì bổ sung vào */
            $redirect_url = $this->nganluong_url;
            if (strpos($redirect_url, '?') === false) {
                $redirect_url .= '?';
            } else if (substr($redirect_url, strlen($redirect_url) - 1, 1) != '?' && strpos($redirect_url, '&') === false) {
                // Nếu biến $redirect_url có '?' nhưng không kết thúc bằng '?' và có chứa dấu '&' thì bổ sung vào cuối
                $redirect_url .= '&';
            }

            /* Bước 3. tạo url */
            $url = '';
            foreach ($arr_param as $key => $value) {
                if ($url == '')
                    $url .= $key . '=' . $value;
                else
                    $url .= '&' . $key . '=' . $value;
            }

            return $redirect_url . $url;
        }

        public function buildCheckoutUrlExpand($return_url, $receiver, $transaction_info, $order_code, $price, $currency = 'vnd', $quantity = 1, $tax = 0, $discount = 0, $fee_cal = 0, $fee_shipping = 0, $order_description = '', $buyer_info = '', $affiliate_code = '')
        {
            // This is from the class provided by Ngan Luong. Not advisable to mess.
            //  This one is for advanced checkout, including taxes and discounts
            if ($affiliate_code == "")
                $affiliate_code = $this->affiliate_code;
            $arr_param = array(
                'merchant_site_code' => strval($this->merchant_site_code),
                'return_url' => strval(strtolower($return_url)),
                'receiver' => strval($receiver),
                'transaction_info' => strval($transaction_info),
                'order_code' => strval($order_code),
                'price' => strval($price),
                'currency' => strval($currency),
                'quantity' => strval($quantity),
                'tax' => strval($tax),
                'discount' => strval($discount),
                'fee_cal' => strval($fee_cal),
                'fee_shipping' => strval($fee_shipping),
                'order_description' => strval($order_description),
                'buyer_info' => strval($buyer_info),
                'affiliate_code' => strval($affiliate_code)
            );
            $secure_code = '';
            $secure_code = implode(' ', $arr_param) . ' ' . $this->secure_pass;
            $arr_param['secure_code'] = md5($secure_code);
            /* */
            $redirect_url = $this->nganluong_url;
            if (strpos($redirect_url, '?') === false) {
                $redirect_url .= '?';
            } else if (substr($redirect_url, strlen($redirect_url) - 1, 1) != '?' && strpos($redirect_url, '&') === false) {
                $redirect_url .= '&';
            }

            /* */
            $url = '';
            foreach ($arr_param as $key => $value) {
                $value = urlencode($value);
                if ($url == '') {
                    $url .= $key . '=' . $value;
                } else {
                    $url .= '&' . $key . '=' . $value;
                }
            }

            return $redirect_url . $url;
        }

        /* Hàm thực hiện xác minh tính đúng đắn của các tham số trả về từ nganluong.vn */

        public static function nganluong_return_handler($order_id)
        {

            global $woocommerce;
            global $wpdb;

            // This probably could be written better
            if (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) && $_REQUEST['error_code'] == '00') {
//                echo "<pre>";var_dump($_REQUEST);echo "</pre>";exit();
                self::log($_SERVER['REMOTE_ADDR'] . json_encode(@$_REQUEST));
                $settings = get_option('woocommerce_nganluong_settings', null);
                $order_id = $_REQUEST['order_id'];
                $nlcheckout = new NL_CheckOutV3(MERCHANT_ID, MERCHANT_PASS, RECEIVER, URL_API);
                $nl_result = $nlcheckout->GetTransactionDetail($_GET['token']);
//                echo "<pre>";var_dump($nl_result);echo "</pre>";exit();
                $order = new WC_Order($order_id);

                // Xác thực mã của chủ web với mã trả về từ nganluong.vn
                // status tạm giữ 2 ngày nên để chế độ pending
//                $new_order_status = $settings['status_order'];
                // tuy nhiên ta sẽ fix cứng status này là completed
                $new_order_status = 'wc-completed';
                $old_status = 'wc-' . $order->get_status();
                if ($new_order_status !== $old_status) {
                    $note = 'Thanh toán trực tuyến qua Ngân Lượng.';
                    if ((string)$nl_result->payment_type == 2) {
                        $note .= ' Với hình thức thanh toán tạm giữ';
                    } else if ((string)$nl_result->payment_type == 1) {
                        $note .= ' Với hình thức thanh toán ngay';
                    }
                    $note .= ' .Mã thanh toán: ' . (string)$nl_result->transaction_id;
                    $order->update_status($new_order_status);
                    $order->add_order_note(sprintf(__('Cập nhật trạng thái từ %1$s thành %2$s.' . $note, 'woocommerce'), wc_get_order_status_name($old_status), wc_get_order_status_name($new_order_status)), 0, false);
                    $new_status = $nlcheckout->GetErrorMessage((string)$nl_result->transaction_status);
                    self::log('Cập nhật đơn hàng ID: ' . (string)$nl_result->order_code . ' trạng thái ' . $new_status);
                }

                // Remove cart
                $woocommerce->cart->empty_cart();
                // Empty awaiting payment session
                unset($_SESSION['order_awaiting_payment']);
                wp_redirect(get_permalink($settings['redirect_page_id']));
                exit;
            }
        }

    }

    class NL_CheckOutV3
    {
        public $url_api = 'https://sandbox.nganluong.vn:8088/nl30/checkout.api.nganluong.post.php';
        public $merchant_id = '';
        public $merchant_password = '';
        public $receiver_email = '';
        public $cur_code = 'vnd';


        function __construct($merchant_id, $merchant_password, $receiver_email, $url_api)
        {
            $this->version = '3.1';
            $this->url_api = $url_api;
            $this->merchant_id = $merchant_id;
            $this->merchant_password = $merchant_password;
            $this->receiver_email = $receiver_email;
        }

        function GetTransactionDetail($token)
        {
            ###################### BEGIN #####################
            $params = array(
                'merchant_id' => $this->merchant_id,
                'merchant_password' => MD5($this->merchant_password),
                'version' => $this->version,
                'function' => 'GetTransactionDetail',
                'token' => $token
            );

            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url_api);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($result != '' && $status == 200) {
                $nl_result = simplexml_load_string($result);
                return $nl_result;
            }

            return false;
            ###################### END #####################

        }


        /*

        Hàm lấy link thanh toán bằng thẻ visa
        ===============================
        Tham số truyền vào bắt buộc phải có
                    order_code
                    total_amount
                    payment_method

                    buyer_fullname
                    buyer_email
                    buyer_mobile
        ===============================
            $array_items mảng danh sách các item name theo quy tắc
            item_name1
            item_quantity1
            item_amount1
            item_url1
            .....
            payment_type Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
         */
        function VisaCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount,
                              $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                              $buyer_address, $array_items, $bank_code)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'VISA', //Phương thức thanh toán, nhận một trong các giá trị 'VISA','ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Phương thức thanh toán, nhận một trong các giá trị 'VISA','ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );
            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }
            //die($post_field);

            $nl_result = $this->CheckoutCall($post_field);
            return $nl_result;
        }

        function PrepaidVisaCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items, $bank_code)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => Config::$_FUNCTION,
                'version' => Config::$_VERSION,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'CREDIT_CARD_PREPAID', //Phương thức thanh toán, nhận một trong các giá trị 'VISA','ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Phương thức thanh toán, nhận một trong các giá trị 'VISA','ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );
            //var_dump($params); exit;
            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '')
                    $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '')
                            $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }
            //die($post_field);

            $nl_result = $this->CheckoutCall($post_field);
            return $nl_result;
        }

        /*
        Hàm lấy link thanh toán qua ngân hàng
        ===============================
        Tham số truyền vào bắt buộc phải có
                    order_code
                    total_amount
                    bank_code // Theo bảng mã ngân hàng

                    buyer_fullname
                    buyer_email
                    buyer_mobile
        ===============================

            $array_items mảng danh sách các item name theo quy tắc
            item_name1
            item_quantity1
            item_amount1
            item_url1
            .....
            payment_type Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn

        */
        function BankCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount,
                              $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                              $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'ATM_ONLINE', //Phương thức thanh toán, nhận một trong các giá trị 'ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Mã Ngân hàng
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );

            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }
            //$post_field="function=SetExpressCheckout&version=3.1&merchant_id=24338&receiver_email=payment@hellochao.com&merchant_password=5b39df2b8f3275d1c8d1ea982b51b775&order_code=macode_oerder123&total_amount=2000&payment_method=ATM_ONLINE&bank_code=ICB&payment_type=&order_description=&tax_amount=0&fee_shipping=0&discount_amount=0&return_url=http://localhost/testcode/nganluong.vn/checkoutv3/payment_success.php&cancel_url=http://nganluong.vn&buyer_fullname=Test&buyer_email=saritvn@gmail.com&buyer_mobile=0909224002&buyer_address=&total_item=1&item_name1=Product name&item_quantity1=1&item_amount1=2000&item_url1=http://nganluong.vn/"	;
            //echo $post_field;
            //die;
            $nl_result = $this->CheckoutCall($post_field);

            return $nl_result;
        }

        function BankOfflineCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount,
                                     $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                                     $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'ATM_OFFLINE', //Phương thức thanh toán, nhận một trong các giá trị 'ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Mã Ngân hàng
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );

            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }
            //$post_field="function=SetExpressCheckout&version=3.1&merchant_id=24338&receiver_email=payment@hellochao.com&merchant_password=5b39df2b8f3275d1c8d1ea982b51b775&order_code=macode_oerder123&total_amount=2000&payment_method=ATM_ONLINE&bank_code=ICB&payment_type=&order_description=&tax_amount=0&fee_shipping=0&discount_amount=0&return_url=http://localhost/testcode/nganluong.vn/checkoutv3/payment_success.php&cancel_url=http://nganluong.vn&buyer_fullname=Test&buyer_email=saritvn@gmail.com&buyer_mobile=0909224002&buyer_address=&total_item=1&item_name1=Product name&item_quantity1=1&item_amount1=2000&item_url1=http://nganluong.vn/"	;
            //echo $post_field;
            //die;
            $nl_result = $this->CheckoutCall($post_field);

            return $nl_result;
        }


        function officeBankCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount,
                                    $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                                    $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'NH_OFFLINE', //Phương thức thanh toán, nhận một trong các giá trị 'ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Mã Ngân hàng
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );

            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }
            //$post_field="function=SetExpressCheckout&version=3.1&merchant_id=24338&receiver_email=payment@hellochao.com&merchant_password=5b39df2b8f3275d1c8d1ea982b51b775&order_code=macode_oerder123&total_amount=2000&payment_method=ATM_ONLINE&bank_code=ICB&payment_type=&order_description=&tax_amount=0&fee_shipping=0&discount_amount=0&return_url=http://localhost/testcode/nganluong.vn/checkoutv3/payment_success.php&cancel_url=http://nganluong.vn&buyer_fullname=Test&buyer_email=saritvn@gmail.com&buyer_mobile=0909224002&buyer_address=&total_item=1&item_name1=Product name&item_quantity1=1&item_amount1=2000&item_url1=http://nganluong.vn/"	;
            //echo $post_field;
            //die;
            $nl_result = $this->CheckoutCall($post_field);

            return $nl_result;
        }

        /*

        Hàm lấy link thanh toán tại văn phòng ngân lượng

        ===============================
        Tham số truyền vào bắt buộc phải có
                    order_code
                    total_amount
                    bank_code // HN hoặc HCM

                    buyer_fullname
                    buyer_email
                    buyer_mobile
        ===============================

            $array_items mảng danh sách các item name theo quy tắc
            item_name1
            item_quantity1
            item_amount1
            item_url1
            .....
            payment_type Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn

        */
        function TTVPCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount,
                              $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                              $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'ATM_ONLINE', //Phương thức thanh toán, nhận một trong các giá trị 'ATM_ONLINE', 'ATM_OFFLINE' hoặc 'NH_OFFLINE'
                'bank_code' => $bank_code, //Mã Ngân hàng
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items)
            );

            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }

            $nl_result = $this->CheckoutCall($post_field);
            return $nl_result;
        }

        /*

        Hàm lấy link thanh toán dùng số dư ví ngân lượng
        ===============================
        Tham số truyền vào bắt buộc phải có
                    order_code
                    total_amount
                    payment_method

                    buyer_fullname
                    buyer_email
                    buyer_mobile
        ===============================
            $array_items mảng danh sách các item name theo quy tắc
            item_name1
            item_quantity1
            item_amount1
            item_url1
            .....

            payment_type Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
         */
        function NLCheckout($order_code, $total_amount, $payment_type, $order_description, $tax_amount,
                            $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile,
                            $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'NL', //Phương thức thanh toán
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items) //Tổng số sản phẩm trong đơn hàng
            );
            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '') $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }

            //die($post_field);
            $nl_result = $this->CheckoutCall($post_field);
            return $nl_result;
        }

        function IBCheckout($order_code, $total_amount, $bank_code, $payment_type, $order_description, $tax_amount, $fee_shipping, $discount_amount, $return_url, $cancel_url, $buyer_fullname, $buyer_email, $buyer_mobile, $buyer_address, $array_items)
        {
            $params = array(
                'cur_code' => $this->cur_code,
                'function' => 'SetExpressCheckout',
                'version' => $this->version,
                'merchant_id' => $this->merchant_id, //Mã merchant khai báo tại NganLuong.vn
                'receiver_email' => $this->receiver_email,
                'merchant_password' => MD5($this->merchant_password), //MD5(Mật khẩu kết nối giữa merchant và NganLuong.vn)
                'order_code' => $order_code, //Mã hóa đơn do website bán hàng sinh ra
                'total_amount' => $total_amount, //Tổng số tiền của hóa đơn
                'payment_method' => 'IB_ONLINE', //Phương thức thanh toán
                'bank_code' => $bank_code,
                'payment_type' => $payment_type, //Kiểu giao dịch: 1 - Ngay; 2 - Tạm giữ; Nếu không truyền hoặc bằng rỗng thì lấy theo chính sách của NganLuong.vn
                'order_description' => $order_description, //Mô tả đơn hàng
                'tax_amount' => $tax_amount, //Tổng số tiền thuế
                'fee_shipping' => $fee_shipping, //Phí vận chuyển
                'discount_amount' => $discount_amount, //Số tiền giảm giá
                'return_url' => $return_url, //Địa chỉ website nhận thông báo giao dịch thành công
                'cancel_url' => $cancel_url, //Địa chỉ website nhận "Hủy giao dịch"
                'buyer_fullname' => $buyer_fullname, //Tên người mua hàng
                'buyer_email' => $buyer_email, //Địa chỉ Email người mua
                'buyer_mobile' => $buyer_mobile, //Điện thoại người mua
                'buyer_address' => $buyer_address, //Địa chỉ người mua hàng
                'total_item' => count($array_items) //Tổng số sản phẩm trong đơn hàng
            );
            $post_field = '';
            foreach ($params as $key => $value) {
                if ($post_field != '')
                    $post_field .= '&';
                $post_field .= $key . "=" . $value;
            }
            if (count($array_items) > 0) {
                foreach ($array_items as $array_item) {
                    foreach ($array_item as $key => $value) {
                        if ($post_field != '')
                            $post_field .= '&';
                        $post_field .= $key . "=" . $value;
                    }
                }
            }

            //die($post_field);
            $nl_result = $this->CheckoutCall($post_field);
            return $nl_result;
        }

        function CheckoutCall($post_field)
        {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url_api);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
            $result = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($result != '' && $status == 200) {
                $xml_result = str_replace('&', '&amp;', (string)$result);
                $nl_result = simplexml_load_string($xml_result);
                $nl_result->error_message = $this->GetErrorMessage($nl_result->error_code);
            } else {
                $nl_result->error_message = $error;
            }
            return $nl_result;

        }

        function GetErrorMessage($error_code)
        {
            $arrCode = array(
                '00' => 'Thành công',
                '99' => 'Lỗi chưa xác minh',
                '06' => 'Mã merchant không tồn tại hoặc bị khóa',
                '02' => 'Địa chỉ IP truy cập bị từ chối',
                '03' => 'Mã checksum không chính xác, truy cập bị từ chối',
                '04' => 'Tên hàm API do merchant gọi tới không hợp lệ (không tồn tại)',
                '05' => 'Sai version của API',
                '07' => 'Sai mật khẩu của merchant',
                '08' => 'Địa chỉ email tài khoản nhận tiền không tồn tại',
                '09' => 'Tài khoản nhận tiền đang bị phong tỏa giao dịch',
                '10' => 'Mã đơn hàng không hợp lệ',
                '11' => 'Số tiền giao dịch lớn hơn hoặc nhỏ hơn quy định',
                '12' => 'Loại tiền tệ không hợp lệ',
                '29' => 'Token không tồn tại',
                '80' => 'Không thêm được đơn hàng',
                '81' => 'Đơn hàng chưa được thanh toán',
                '110' => 'Địa chỉ email tài khoản nhận tiền không phải email chính',
                '111' => 'Tài khoản nhận tiền đang bị khóa',
                '113' => 'Tài khoản nhận tiền chưa cấu hình là người bán nội dung số',
                '114' => 'Giao dịch đang thực hiện, chưa kết thúc',
                '115' => 'Giao dịch bị hủy',
                '118' => 'tax_amount không hợp lệ',
                '119' => 'discount_amount không hợp lệ',
                '120' => 'fee_shipping không hợp lệ',
                '121' => 'return_url không hợp lệ',
                '122' => 'cancel_url không hợp lệ',
                '123' => 'items không hợp lệ',
                '124' => 'transaction_info không hợp lệ',
                '125' => 'quantity không hợp lệ',
                '126' => 'order_description không hợp lệ',
                '127' => 'affiliate_code không hợp lệ',
                '128' => 'time_limit không hợp lệ',
                '129' => 'buyer_fullname không hợp lệ',
                '130' => 'buyer_email không hợp lệ',
                '131' => 'buyer_mobile không hợp lệ',
                '132' => 'buyer_address không hợp lệ',
                '133' => 'total_item không hợp lệ',
                '134' => 'payment_method, bank_code không hợp lệ',
                '135' => 'Lỗi kết nối tới hệ thống ngân hàng',
                '140' => 'Đơn hàng không hỗ trợ thanh toán trả góp',);

            return $arrCode[(string)$error_code];
        }


    }

    function woocommerce_add_NganLuong_gateway($methods)
    {
        $methods[] = 'WC_Gateway_NganLuong';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_NganLuong_gateway');
}


