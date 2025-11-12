<?php
// ১. Composer Autoloader লোড করা
require_once __DIR__ . '/vendor/autoload.php';

// ২. JSON ফাইলের নাম নির্ধারণ করা
// আমরা URL থেকে 'invoice' প্যারামিটারটি নেব। যেমন: download_invoice.php?invoice=INV-1234
$invoice_no = $_GET['invoice'] ?? 'example_invoice';
$dir = 'invoices/';
$filename_safe = preg_replace('/[^a-zA-Z0-9-]/', '_', $invoice_no);
$json_file_path = $dir . $filename_safe . '.json';

$form_data = [];

// ৩. JSON ডেটা লোড করা
if (file_exists($json_file_path)) {
    $json_content = @file_get_contents($json_file_path);
    if ($json_content) {
        $form_data = json_decode($json_content, true);
    }
}

// ডেটা না থাকলে বা লোড করতে ব্যর্থ হলে একটি এরর মেসেজ দেখান
if (empty($form_data)) {
    die("Error: Invoice data could not be loaded for invoice number: " . htmlspecialchars($invoice_no));
}

// === ৪. HTML কন্টেন্ট তৈরি শুরু করা ===
// আউটপুট বাফারিং শুরু করুন
ob_start();

// আপনার মূল কোডের মধ্যে <div class="invoice-page"> থেকে </div> পর্যন্ত সমস্ত HTML প্রিভিউ কোড এখানে লাগবে। 
// যেহেতু $form_data লোড হয়েছে, তাই আপনি সরাসরি ইনভয়েস টেমপ্লেট ব্যবহার করতে পারেন। 
// **গুরুত্বপূর্ণ:** শুধু ইনভয়েস পেজের ভেতরের HTML টুকু দিন।
?>
<style>
    /* mPDF এর জন্য প্রয়োজনীয় CSS এখানে যোগ করুন। 
    আপনার মূল কোডের A4 Invoice Preview Styles (যেখানে .invoice-page আছে)
    এবং টেবিল স্টাইলগুলি এখানে কপি করে আনুন।
*/
    .invoice-page {
        width: 210mm;
        padding: 15mm;
    }

    /* ... বাকি CSS ... */
</style>

<div class="invoice-page">

    <table style="width: 100%">
        <tr>
            <td colspan="3" style="text-align: right;">
                <h1 style="margin: 0px !important;">INVOICE</h1>
            </td>
        </tr>
        <tr style="font-size: 12px;">
            <td style="width: 15%">
                <?php
                $logo_file = $form_data['vendor_logo'] ?? 'No File Selected';
                if ($logo_file !== 'No File Selected' && file_exists('uploads/' . $logo_file)):
                ?>
                    <img src="uploads/<?php echo htmlspecialchars($logo_file); ?>" width="80" alt="Vendor Logo">
                <?php endif; ?>
            </td>
            <td style="width: 30%">
                <span style="display:block; font-weight:bold; margin:5px 0;">
                    <?php echo htmlspecialchars($form_data['vendor_title'] ?? 'N/A'); ?>
                </span>
                <span>
                    <?php echo htmlspecialchars($form_data['vendor_address'] ?? 'N/A'); ?>
                </span>
                <span style="display: block; margin:5px 0;">Phone: <?php echo htmlspecialchars($form_data['vendor_phone_no'] ?? 'N/A'); ?></span>
            </td>
            <td style="width: 55%; text-align: right; vertical-align: top;">
                <span style="display: block;"><?php echo htmlspecialchars($form_data['invoice_no'] ?? 'N/A'); ?></span>
                <span style="display: block;"><strong>Date:</strong> <?php echo htmlspecialchars($form_data['date'] ?? 'N/A'); ?></span>
            </td>
        </tr>
    </table>

    <table style="width: 100%; font-size: 12px; margin-top: 15px;">
        <tr>
            <td style="width: 45%">
                <h3 style="margin: 5px 0px !important;">Bill To:</h3>
                <span style="display: block;"><?php echo htmlspecialchars($form_data['client_title'] ?? 'N/A'); ?></span>
                <span style="display: block;"><?php echo htmlspecialchars($form_data['client_address'] ?? 'N/A'); ?>
                </span>
                <?php if (!empty($form_data['client_cc'])): ?>
                    <span style="display: block;">CC: <?php echo htmlspecialchars($form_data['client_cc']); ?></span>
                <?php endif; ?>
                <span style="display: block;"><?php echo htmlspecialchars($form_data['client_phone_no'] ?? 'N/A'); ?></span>
            </td>
            <td style="width: 55%"></td>
        </tr>
    </table>

    <table style="width: 100%; font-size: 12px; margin-top: 15px; border-collapse: collapse;">
        <thead style="background-color: #dedede">
            <tr>
                <th style="width: 48%; padding: 6px; text-align: left;">Item</th>
                <th style="width: 15%; padding: 6px; text-align: center;">Quantity</th>
                <th style="width: 20%; padding: 6px; text-align: right;">Rate</th>
                <th style="width: 37%; padding: 6px; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($form_data['work_items'] as $item): ?>
                <tr>
                    <td style="border-bottom: 1px solid #d4d4d4; padding: 6px; vertical-align: top;">
                        <strong><?php echo htmlspecialchars($item['work_title']); ?></strong><br>
                        <small style="color: #666;">
                            <?php echo nl2br(htmlspecialchars($item['work_particular'])); ?>
                        </small>
                    </td>
                    <td style="border-bottom: 1px solid #d4d4d4; padding: 6px; text-align: center;">
                        <?php echo htmlspecialchars($item['work_qty']); ?>
                    </td>
                    <td style="border-bottom: 1px solid #d4d4d4; padding: 6px; text-align: right;">
                        <strong><?php echo htmlspecialchars(number_format($item['work_rate'], 2)); ?></strong>
                    </td>
                    <td style="border-bottom: 1px solid #d4d4d4; padding: 6px; text-align: right;">
                        <strong><?php echo htmlspecialchars(number_format($item['amount'], 2)); ?></strong>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2"></td>
                <th style="display: block; margin: 5px 0px;"><strong>Total Amount:</strong></th>
                <td><strong>BDT <?php echo htmlspecialchars(number_format($form_data['total_amount'] ?? 0, 2)); ?></strong>
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <th style="display: block; margin: 5px 0px;"><strong>Paid Amount:</strong></th>
                <td><strong>BDT <?php echo htmlspecialchars(number_format($form_data['paid_amount'] ?? 0, 2)); ?></strong>
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <th style="display: block; padding: 5px 0px; background-color: #f5f5f5;"><strong>Balance Due:</strong></th>
                <td style="background-color: #f5f5f5;"><strong>BDT <?php echo htmlspecialchars(number_format($form_data['due_amount'] ?? 0, 2)); ?></strong>
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 5px; font-size: 12px">
        <tr>
            <td style="width: 100%">In Word: <?php echo htmlspecialchars($form_data['amount_in_word'] ?? 'N/A'); ?></td>
        </tr>
    </table>

    <table style="width: 100%; font-size: 12px; margin-top: 20px;">
        <tr>
            <td style="width: 100%;"><strong>Bank Info:</strong></td>
        </tr>
        <?php foreach ($form_data['bank_items'] as $item): ?>
            <?php if (!empty($item['vendor_bank']) || !empty($item['vendor_mfs_title'])): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['vendor_bank'])): ?>
                            <span><strong>Bank:</strong> <?php echo htmlspecialchars($item['vendor_bank']); ?> | A/C:
                                <?php echo htmlspecialchars($item['vendor_bank_account']); ?> | Branch:
                                <?php echo htmlspecialchars($item['vendor_bank_branch']); ?> | Routing:
                                <?php echo htmlspecialchars($item['vendor_bank_routing']); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php if (!empty($item['vendor_mfs_title'])): ?>
                            <span><strong>MFS:</strong> <?php echo htmlspecialchars($item['vendor_mfs_title']); ?>
                                (<?php echo htmlspecialchars($item['vendor_mfs_type']); ?>) | Account:
                                <?php echo htmlspecialchars($item['vendor_mfs_account']); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php if (!empty($item['vendor_amount_note'])): ?>
                            <span>Note: <?php echo htmlspecialchars($item['vendor_amount_note']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>

    <div class="software-note">
        ---This is a software-generated invoice. No need for a sign and seal.---
    </div>
</div>

<?php
// ৫. HTML কন্টেন্ট ক্যাপচার করা এবং বাফারিং বন্ধ করা
$html = ob_get_clean();

// ৬. mPDF ইনিশিয়ালাইজ করা
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => __DIR__ . '/tmp' // নিশ্চিত করুন যে 'tmp' ফোল্ডারটি বিদ্যমান এবং রাইট পারমিশন আছে
]);

// ৭. HTML লেখা এবং রেন্ডার করা
$mpdf->WriteHTML($html);

// ৮. PDF ফাইল আউটপুট করা (ব্রাউজারে ডাউনলোড হিসেবে)
$mpdf->Output("Invoice_{$invoice_no}.pdf", 'D'); // 'D' মানে ডাউনলোড
exit;
?>