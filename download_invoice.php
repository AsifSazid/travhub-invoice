<?php
// ১. Composer Autoloader লোড করা
require_once __DIR__ . '/vendor/autoload.php';

// ২. JSON ফাইলের নাম নির্ধারণ করা
$invoice_no = 'example_invoice'; // উদাহরণস্বরূপ
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

// ডেটা না থাকলে এরর
if (empty($form_data)) {
    die("Error: Invoice data could not be loaded for invoice number: " . htmlspecialchars($invoice_no));
}

// === ৪. HTML কন্টেন্ট তৈরি শুরু ===
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            font-size: 12px;
        }

        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 180px;
            /* এখানে বাড়াও যতক্ষণ না কনটেন্ট ঠিকভাবে নিচে নামে */
            margin-bottom: 80px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 0px;
        }

        .title {
            font-size: 25px;
            font-weight: bold;
        }

        .sub-title {
            font-size: 15px;
            font-weight: bold;
        }

        .no-border td,
        .no-border th {
            border: none !important;
        }
    </style>
</head>

<body>

    <!-- === FIXED HEADER === -->
    <htmlpageheader name="page-header">
        <table class="no-border">
            <tr>
                <td colspan="3" style="text-align: right; border:none;">
                    <h1 style="margin: 0px;" class="title">INVOICE</h1>
                </td>
            </tr>
            <tr>
                <td rowspan="3" style="width: 12%; border:none;">
                    <?php
                    $logo_file = $form_data['vendor_logo'] ?? 'No File Selected';
                    if ($logo_file !== 'No File Selected' && file_exists('uploads/' . $logo_file)):
                    ?>
                        <img src="uploads/<?php echo htmlspecialchars($logo_file); ?>" width="65" alt="Vendor Logo">
                    <?php endif; ?>
                </td>
                <td style="width: 30%; border:none;">
                    <div style="font-weight:bold;" class="sub-title">
                        <?php echo htmlspecialchars($form_data['vendor_title'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <?php
                        // Address Line 1
                        if (!empty($form_data['vendor_address_line_01'])) {
                            echo '<div style="display:block;">' . htmlspecialchars($form_data['vendor_address_line_01']) . '</div>';
                        }

                        // Address Line 2 (with comma only if next value exists)
                        if (!empty($form_data['vendor_address_line_02'])) {
                            echo '<span>';
                            echo htmlspecialchars($form_data['vendor_address_line_02']);
                            // Add comma only if city or postal code exists
                            if (!empty($form_data['vendor_address_city']) || !empty($form_data['vendor_address_postal_code'])) {
                                echo ', ';
                            }
                            echo '</span>';
                        }

                        // City & Postal Code (with hyphen only if postal code exists)
                        if (!empty($form_data['vendor_address_city']) || !empty($form_data['vendor_address_postal_code'])) {
                            echo '<span>';
                            if (!empty($form_data['vendor_address_city'])) {
                                echo htmlspecialchars($form_data['vendor_address_city']);
                            }
                            if (!empty($form_data['vendor_address_city']) && !empty($form_data['vendor_address_postal_code'])) {
                                echo '-';
                            }
                            if (!empty($form_data['vendor_address_postal_code'])) {
                                echo htmlspecialchars($form_data['vendor_address_postal_code']);
                            }
                            echo '</span>';
                        }
                        ?>
                    </div>
                    <div>Phone: <?php echo htmlspecialchars($form_data['vendor_phone_no'] ?? 'N/A'); ?></div>
                </td>
                <td style="width: 58%; text-align: right; vertical-align: top; border:none; padding-top: 10px;">
                    <div style="display: block;"><?php echo htmlspecialchars($form_data['invoice_no'] ?? 'N/A'); ?></div>
                    <div style="display: block;"><strong>Date:</strong> <?php echo htmlspecialchars($form_data['date'] ?? 'N/A'); ?></div>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- === FIXED FOOTER === -->
    <htmlpagefooter name="page-footer">
        <table class="no-border" style="font-size:10px; text-align:center;">
            <tr>
                <td style="border:none;">---This is a software-generated invoice. No need for a sign and seal.---</td>
            </tr>
            <tr>
                <td style="text-align:right; border:none;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>


    <!-- === MAIN CONTENT (Invoice Body) === -->
    <div class="content-body">

        <table class="no-border" style="margin-top: 5px;">
            <tr>
                <td style="width: 45%">
                    <h3 style="margin: 5px 0;">Bill To:</h3>
                    <span><?php echo htmlspecialchars($form_data['client_title'] ?? 'N/A'); ?></span><br>
                    <?php
                    // Address Line 1
                    if (!empty($form_data['client_address_line_01'])) {
                        echo '<div style="display:block;">' . htmlspecialchars($form_data['client_address_line_01']) . '</div>';
                    }

                    // Address Line 2 (with comma only if next value exists)
                    if (!empty($form_data['vendor_address_line_02'])) {
                        echo '<span>';
                        echo htmlspecialchars($form_data['client_address_line_02']);
                        // Add comma only if city or postal code exists
                        if (!empty($form_data['client_address_city']) || !empty($form_data['client_address_postal_code'])) {
                            echo ', ';
                        }
                        echo '</span>';
                    }

                    // City & Postal Code (with hyphen only if postal code exists)
                    if (!empty($form_data['client_address_city']) || !empty($form_data['client_address_postal_code'])) {
                        echo '<span>';
                        if (!empty($form_data['client_address_city'])) {
                            echo htmlspecialchars($form_data['vendor_address_city']);
                        }
                        if (!empty($form_data['client_address_city']) && !empty($form_data['client_address_postal_code'])) {
                            echo '-';
                        }
                        if (!empty($form_data['client_address_postal_code'])) {
                            echo htmlspecialchars($form_data['client_address_postal_code']);
                        }
                        echo '</span>';
                    }
                    ?>
                    </span>
                    <br>
                    <?php if (!empty($form_data['client_cc'])): ?>
                        <span>CC: <?php echo htmlspecialchars($form_data['client_cc']); ?></span><br>
                    <?php endif; ?>
                    <span>Phone: <?php echo htmlspecialchars($form_data['client_phone_no'] ?? 'N/A'); ?></span>
                </td>
            </tr>
        </table>

        <table class="no-border" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th style="background-color: #ccccccff; width: 50%; text-align: left; padding: 5px">Item</th>
                    <th style="background-color: #ccccccff; width: 10%; text-align: center;">Qty</th>
                    <th style="background-color: #ccccccff; width: 20%; text-align: right;">Rate</th>
                    <th style="background-color: #ccccccff; width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($form_data['work_items'] as $item): ?>
                    <tr>
                        <td style="padding: 10px 5px; border-bottom: 1px solid #d4d4d4;">
                            <strong><?php echo htmlspecialchars($item['work_title']); ?></strong><br>
                            <small><?php echo nl2br(htmlspecialchars($item['work_particular'])); ?></small>
                        </td>
                        <td style="text-align: center; border-bottom: 1px solid #d4d4d4;"><?php echo htmlspecialchars($item['work_qty']); ?></td>
                        <td style="text-align: right; border-bottom: 1px solid #d4d4d4;"><?php echo htmlspecialchars(number_format($item['work_rate'])); ?></td>
                        <td style="text-align: right; border-bottom: 1px solid #d4d4d4;"><?php echo htmlspecialchars(number_format($item['work_qty'] * $item['work_rate'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2"></td>
                    <th style="text-align: right; padding: 5px 0px 5px 5px;">Total Amount:</th>
                    <td style="padding-left: 5px; text-align: right;">BDT <?php echo htmlspecialchars(number_format($form_data['total_amount'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <th style="text-align: right; padding: 5px 0px 5px 5px;">Paid Amount:</th>
                    <td style="padding-left: 5px; text-align: right;">BDT <?php echo htmlspecialchars(number_format($form_data['paid_amount'] ?? 0)); ?></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <th style="background-color: #f5f5f5; text-align: right; padding: 5px 0px 5px 5px;" class="sub-title">Balance Due:</th>
                    <td style="background-color: #f5f5f5; padding-left: 8px; text-align: right;" class="sub-title">BDT <?php echo htmlspecialchars(number_format($form_data['due_amount'] ?? 0)); ?></td>
                </tr>
            </tbody>
        </table>

        <table class="no-border" style="font-size: 12px; margin-top: 10px;">
            <tr>
                <td>In Word: <?php echo htmlspecialchars($form_data['amount_in_word'] ?? 'N/A'); ?></td>
            </tr>
        </table>

        <table class="no-border" style="font-size: 12px; margin-top: 15px;">
            <tr>
                <td style="padding: 5px 0px;"><strong>Bank Info:</strong></td>
            </tr>
            <?php foreach ($form_data['bank_items'] as $item): ?>
                <?php if (!empty($item['vendor_bank'])): ?>
                    <tr>
                        <td style="border:none;">
                            <?php echo htmlspecialchars($item['vendor_bank']); ?> | A/C:
                            <?php echo htmlspecialchars($item['vendor_bank_account']); ?> | Branch:
                            <?php echo htmlspecialchars($item['vendor_bank_branch']); ?> | Routing:
                            <?php echo htmlspecialchars($item['vendor_bank_routing']); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php foreach ($form_data['mfs_items'] as $item): ?>
                <?php if (!empty($item['vendor_mfs_title'])): ?>
                    <tr>
                        <td style="border:none;">
                            <?php echo htmlspecialchars($item['vendor_mfs_title']); ?> |
                            <?php echo htmlspecialchars($item['vendor_mfs_type']); ?> | Account:
                            <?php echo htmlspecialchars(implode(' | ', $item['vendor_mfs_account'])); ?>
                        </td>
                    </tr>
                    <?php if (!empty($item['vendor_amount_note'])): ?>
                        <tr>
                            <td>Note: <?php echo htmlspecialchars($item['vendor_amount_note']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>

    </div>
</body>

</html>

<?php
// ৫. HTML কন্টেন্ট ক্যাপচার
$html = ob_get_clean();

// ৬. mPDF ইনিশিয়ালাইজ
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => __DIR__ . '/tmp',
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
        __DIR__ . '/fonts',
    ]),
    'fontdata' => array_merge((new Mpdf\Config\FontVariables())->getDefaults()['fontdata'], [
        'poppins' => [
            'R' => 'Poppins-Regular.ttf',
            'M' => 'Poppins-Medium.ttf',
            'B' => 'Poppins-SemiBold.ttf',
        ]
    ]),
    'default_font' => 'poppins'
]);

// ৭. HTML লেখা ও আউটপুট
$mpdf->WriteHTML($html);
$mpdf->Output("Invoice_{$invoice_no}.pdf", 'I');
exit;
?>