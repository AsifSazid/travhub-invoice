<?php
// PHP কোড শুরু: এই ফাইলটি সার্ভার সাইডে চলতে হবে।

// 1. ফাইল আপলোড ফাংশন
function handleFileUpload($file, $invoice_no, &$form_data)
{
    $uploadDir = 'uploads/';
    $filename = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');

    if ($file['error'] === UPLOAD_ERR_OK && in_array($fileExt, $allowed)) {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // ফাইলকে ইনভয়েস নম্বর দিয়ে রিনেম করা
        $fileNameNew = preg_replace('/[^a-zA-Z0-9-]/', '_', $invoice_no) . '.' . $fileExt;
        $fileDestination = $uploadDir . $fileNameNew;

        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            $form_data['vendor_logo'] = $fileNameNew;
            return true;
        }
    }

    $form_data['vendor_logo'] = 'No File Selected'; // ফলব্যাক
    return false;
}

// 2. ডেটা প্রসেসিং লজিক
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$form_data = [];
$json_save_message = '';
$json_file_path = '';
$dir = 'invoices/';
$default_json_file = $dir . 'example_invoice.json';
$show_preview = false; // নতুন ফ্ল্যাগ: কখন প্রিভিউ দেখাবো?

if ($is_post) {
    // === POST লজিক: ফর্ম সাবমিশন ও JSON সেভ ===
    $form_data = $_POST;
    $invoice_no = $form_data['invoice_no'] ?? 'INV-' . time();

    // === ইমেজ আপলোড হ্যান্ডলিং ===
    if (isset($_FILES['vendor_logo']) && $_FILES['vendor_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        handleFileUpload($_FILES['vendor_logo'], $invoice_no, $form_data);
    } else {
        $form_data['vendor_logo'] = $form_data['existing_vendor_logo'] ?? 'No File Selected';
    }

    // কাজের আইটেমগুলোকে একটি স্ট্রাকচার্ড অ্যারেতে একত্রিত করা হচ্ছে
    $work_items = [];
    if (isset($form_data['work_title']) && is_array($form_data['work_title'])) {
        $count = count($form_data['work_title']);
        for ($i = 0; $i < $count; $i++) {
            $work_items[] = [
                'work_title' => $form_data['work_title'][$i] ?? '',
                'work_particular' => $form_data['work_particular'][$i] ?? '',
                'work_qty' => (float) ($form_data['work_qty'][$i] ?? 0),
                'work_rate' => (float) ($form_data['work_rate'][$i] ?? 0),
                'amount' => (float) ($form_data['amount'][$i] ?? 0),
            ];
        }
    }
    $form_data['work_items'] = $work_items;

    // ব্যাংক আইটেমগুলোকেও একইভাবে স্ট্রাকচার্ড অ্যারেতে একত্রিত করা হচ্ছে
    $bank_items = [];
    if (isset($form_data['vendor_bank']) && is_array($form_data['vendor_bank'])) {
        $count = count($form_data['vendor_bank']);
        for ($i = 0; $i < $count; $i++) {
            $bank_items[] = [
                'vendor_bank' => $form_data['vendor_bank'][$i] ?? '',
                'vendor_bank_account' => $form_data['vendor_bank_account'][$i] ?? '',
                'vendor_bank_branch' => $form_data['vendor_bank_branch'][$i] ?? '',
                'vendor_bank_routing' => $form_data['vendor_bank_routing'][$i] ?? '',
                'vendor_mfs_title' => $form_data['vendor_mfs_title'][$i] ?? '',
                'vendor_mfs_type' => $form_data['vendor_mfs_type'][$i] ?? '',
                'vendor_mfs_account' => $form_data['vendor_mfs_account'][$i] ?? '',
                'vendor_amount_note' => $form_data['vendor_amount_note'][$i] ?? '',
            ];
        }
    }
    $form_data['bank_items'] = $bank_items;

    // অপ্রয়োজনীয় অ্যারে মুছে দেওয়া
    $keys_to_unset = [
        'work_title',
        'work_particular',
        'work_qty',
        'work_rate',
        'amount',
        'vendor_bank',
        'vendor_bank_account',
        'vendor_bank_branch',
        'vendor_bank_routing',
        'vendor_mfs_title',
        'vendor_mfs_type',
        'vendor_mfs_account',
        'vendor_amount_note',
        'existing_vendor_logo'
    ];
    foreach ($keys_to_unset as $key) {
        unset($form_data[$key]);
    }

    // JSON ফাইল সেভ করার লজিক
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $json_data_encoded = json_encode($form_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filename_safe = preg_replace('/[^a-zA-Z0-9-]/', '_', $invoice_no);
    $json_file_path = $dir . $filename_safe . '.json';

    if (file_put_contents($json_file_path, $json_data_encoded) !== false) {
        $json_save_message = '<p style="color: green; font-weight: bold;">✅ Success! Invoice data saved to file: <code>' . htmlspecialchars($json_file_path) . '</code></p>';
    } else {
        $json_save_message = '<p style="color: red; font-weight: bold;">❌ Error! Could not save JSON file. Check directory permissions (<code>' . htmlspecialchars($dir) . '</code>).</p>';
    }

    $show_preview = true; // POST হলে প্রিভিউ দেখাও

} else {
    // === GET লজিক: ডিফল্ট JSON লোড ও প্রিভিউ দেখাও ===
    if (file_exists($default_json_file)) {
        $json_content = @file_get_contents($default_json_file);

        if ($json_content) {
            $form_data = json_decode($json_content, true);
        }

        if ($form_data !== null && $form_data !== []) {
            // ডেটা সফলভাবে লোড হয়েছে: প্রিভিউ দেখাও
            $show_preview = true;
            $json_file_path = $default_json_file; // ডাউনলোড লিংকের জন্য পাথ সেট
            $json_save_message = '<p style="color: blue; font-weight: bold;">ⓘ Default Invoice Preview.</p>';
        } else {
            // JSON ফাইল ইনভ্যালিড/খালি: ফর্ম দেখাও
            $json_save_message = '<p style="color: red; font-weight: bold;">❌ Error! Default JSON file is invalid or empty. Showing form.</p>';
        }
    } else {
        // ফাইল পাওয়া যায়নি: ফর্ম দেখাও
        $json_save_message = '<p style="color: orange; font-weight: bold;">⚠️ Warning: Default JSON file <code>' . htmlspecialchars($default_json_file) . '</code> not found. Showing empty form.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইনভয়েস ডেটা এন্ট্রি এবং প্রিভিউ</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Shared Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
            font-size: 10px !important;
        }

        .title {
            font-size: 25px !important;
            font-weight: 800 !important;
        }

        .sub-title {
            font-size: 15px !important;
            font-weight: 600 !important;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
            color: #007bff;
        }

        /* Form Specific Styles */
        <?php if (!$show_preview): ?>label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        button.form-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .remove-btn {
            background-color: #dc3545;
            margin-left: 10px;
            padding: 5px 10px;
        }

        .work-item,
        .bank-item {
            border: 1px dashed #ccc;
            padding: 15px;
            margin-bottom: 10px;
        }

        .load-json-container {
            text-align: center;
            padding: 15px;
            border: 1px dashed #007bff;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .load-json-container input[type="text"] {
            width: 300px;
            display: inline-block;
            margin-right: 10px;
        }

        .load-json-container button {
            background-color: #007bff;
        }

        .logo-preview {
            margin-top: 5px;
            font-size: 0.9em;
            color: #6c757d;
        }

        <?php endif; ?>

        /* A4 Invoice Preview Styles */
        <?php if ($show_preview): ?>.container {
            padding: 0;
            background: none;
            box-shadow: none;
            max-width: 800px;
        }

        .invoice-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
            position: relative;
        }

        .action-buttons {
            margin-bottom: 15px;
            text-align: center;
        }

        .action-buttons a.json-download {
            background-color: #ffc107;
            color: #333;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-left: 10px;
        }

        /* Updated Design Styles */
        .invoice-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .vendor-logo-box {
            flex-basis: 30%;
        }

        .vendor-logo-box img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
        }

        .invoice-title-meta {
            flex-basis: 30%;
            text-align: right;
        }

        .invoice-title-meta h1 {
            font-size: 2.5em;
            color: #333;
            margin: 0 0 10px 0;
            border-bottom: none;
        }

        .invoice-title-meta p {
            margin: 0;
            line-height: 1.4;
            font-size: 1.1em;
        }

        .invoice-title-meta strong {
            color: #555;
        }

        .address-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .address-box {
            flex-basis: 48%;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .address-box h3 {
            margin-top: 0;
            font-size: 1.1em;
            color: #007bff;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .address-box p {
            margin: 0;
            line-height: 1.5;
            font-size: 0.95em;
        }

        /* Table Styling */
        .work-items-table table {
            border: 1px solid #ccc;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .work-items-table th,
        .work-items-table td {
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 0.95em;
        }

        .work-items-table th {
            background-color: #f0f0f0;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .work-title-col {
            font-weight: bold;
        }

        /* Total Summary */
        .total-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .total-box-custom {
            width: 50%;
            border: 2px solid #007bff;
        }

        .total-box-custom table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .total-box-custom th,
        .total-box-custom td {
            border: none;
            padding: 8px 10px;
        }

        .total-box-custom th {
            text-align: left;
            font-weight: normal;
            background: none;
        }

        .total-box-custom td {
            text-align: right;
            font-weight: bold;
        }

        .due-row td,
        .due-row th {
            background-color: #f7f7ff;
            color: #dc3545;
            border-top: 1px solid #007bff;
            font-weight: bold !important;
            font-size: 1.1em;
        }

        .amount-in-word-section {
            margin-top: 15px;
            font-size: 1.05em;
        }

        /* Bank Info */
        .bank-section-custom {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .bank-section-custom h4 {
            color: #555;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .bank-item-detail {
            margin-bottom: 8px;
            font-size: 0.9em;
            line-height: 1.4;
            border-left: 3px solid #007bff;
            padding-left: 10px;
        }

        /* Footer */
        .software-note {
            position: absolute;
            bottom: 10mm;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7em;
            color: #999;
            text-align: center;
        }

        <?php endif; ?>
        /* Print Styles */
        @media print {
            body {
                background: none;
            }

            .container {
                max-width: none;
                box-shadow: none;
            }

            .invoice-page {
                box-shadow: none;
                margin: 0;
                padding: 0;
                min-height: initial;
            }

            .form-container,
            .action-buttons,
            .json-save-message {
                display: none;
            }

            .invoice-page {
                padding: 15mm;
            }
        }
    </style>
</head>

<body>

    <?php if ($show_preview): ?>

        <div class="container">
            <div class="json-save-message" style="text-align: center; margin-bottom: 15px;">
                <?php echo $json_save_message; ?>
            </div>

            <!-- <div class="action-buttons">
                <button onclick="window.print()" style="background-color: #007bff;">🖨️ Print / Save as PDF</button>
                <?php if (!empty($json_file_path)): ?>
                    <a href="<?php echo htmlspecialchars($json_file_path); ?>" class="json-download" download>⬇️ Download
                        JSON</a>
                <?php endif; ?>
                <button onclick="window.location.href = window.location.href.split('?')[0];"
                    style="background-color: #555;">⬅️ Go Back to Form</button>
            </div> -->

            <div class="action-buttons">
                <?php
                // নিশ্চিত করুন যে $form_data['invoice_no'] সেট করা আছে
                $current_invoice_no = $form_data['invoice_no'] ?? 'example_invoice';
                ?>

                <a href="download_invoice.php?invoice=<?php echo htmlspecialchars($current_invoice_no); ?>"
                    target="_blank"
                    style="background-color: #dc3545; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block; margin-right: 10px;">
                    ⬇️ mPDF-এ PDF ডাউনলোড করুন
                </a>

                <button onclick="window.print()" style="background-color: #007bff;">🖨️ প্রিন্ট/সেভ করুন</button>

                <?php if (!empty($json_file_path)): ?>
                    <a href="<?php echo htmlspecialchars($json_file_path); ?>" class="json-download" download>⬇️ Download JSON</a>
                <?php endif; ?>
                <button onclick="window.location.href = window.location.href.split('?')[0];" style="background-color: #555;">⬅️ ফর্মে ফিরে যান</button>
            </div>

            <div class="invoice-page">

                <table style="width: 100%">
                    <tr>
                        <td colspan="3" style="text-align: right;">
                            <h1 style="margin: 0px !important;" class="title">INVOICE</h1>
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
                            <span style="display:block;" class="sub-title">
                                <?php echo htmlspecialchars($form_data['vendor_title'] ?? 'N/A'); ?>
                            </span>

                            <?php
                            // Address Line 1
                            if (!empty($form_data['vendor_address_line_01'])) {
                                echo '<span style="display:block;">' . htmlspecialchars($form_data['vendor_address_line_01']) . '</span>';
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

                            <span style="display: block;">
                                Phone: <?php echo htmlspecialchars($form_data['vendor_phone_no'] ?? 'N/A'); ?>
                            </span>
                        </td>

                        <td style="width: 55%; text-align: right; vertical-align: top;">
                            <span style="display: block;"><?php echo htmlspecialchars($form_data['invoice_no'] ?? 'N/A'); ?></span>
                            <span style="display: block;"><strong>Date:</strong> <?php echo htmlspecialchars($form_data['date'] ?? 'N/A'); ?></span>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; margin-top: 15px;">
                    <tr>
                        <td style="width: 45%">
                            <h3 style="margin: 5px 0px !important;" class="sub-title">Bill To:</h3>
                            <span style="display: block;"><?php echo htmlspecialchars($form_data['client_title'] ?? 'N/A'); ?></span>
                            <?php
                            // Address Line 1
                            if (!empty($form_data['client_address_line_01'])) {
                                echo '<span style="display:block;">' . htmlspecialchars($form_data['client_address_line_01']) . '</span>';
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
                            <?php if (!empty($form_data['client_cc'])): ?>
                                <span style="display: block;">CC: <?php echo htmlspecialchars($form_data['client_cc']); ?></span>
                            <?php endif; ?>
                            <span style="display: block;"><?php echo htmlspecialchars($form_data['client_phone_no'] ?? 'N/A'); ?></span>
                        </td>
                        <td style="width: 55%"></td>
                    </tr>
                </table>

                <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
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
                                    <span class="sub-title"><strong><?php echo htmlspecialchars($item['work_title']); ?></strong></span><br>
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

                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="width: 100%;"><strong>Bank Info:</strong></td>
                    </tr>
                    <?php foreach ($form_data['bank_items'] as $item): ?>
                        <?php if (!empty($item['vendor_bank'])): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['vendor_bank'])): ?>
                                        <span><?php echo htmlspecialchars($item['vendor_bank']); ?> | A/C:
                                            <?php echo htmlspecialchars($item['vendor_bank_account']); ?> | Branch:
                                            <?php echo htmlspecialchars($item['vendor_bank_branch']); ?> | Routing:
                                            <?php echo htmlspecialchars($item['vendor_bank_routing']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($form_data['mfs_items'] as $item): ?>
                        <?php if (!empty($item['vendor_mfs_title'])): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['vendor_mfs_title'])): ?>
                                        <span><?php echo htmlspecialchars($item['vendor_mfs_title']); ?> |
                                            <?php echo htmlspecialchars($item['vendor_mfs_type']); ?> | Account:
                                            <?php
                                            $lastIndex = count($item['vendor_mfs_account']) - 1;
                                            foreach ($item['vendor_mfs_account'] as $i => $vendorAcc):
                                                echo htmlspecialchars($vendorAcc);
                                                if ($i !== $lastIndex) {
                                                    echo " | ";
                                                }
                                            endforeach;
                                            ?>
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
        </div>

    <?php else: ?>

        <div class="container form-container">
            <h2>📋 ইনভয়েস ডেটা এন্ট্রি ফর্ম</h2>

            <div class="load-json-container">
                <?php echo $json_save_message; ?>
                <label for="json_file_name" style="font-weight: normal; display: inline;">JSON ফাইল থেকে ডেটা লোড
                    করুন:</label>
                <input type="text" id="json_file_name" placeholder="invoices/INV-1234567890.json"
                    value="invoices/example_invoice.json">
                <button onclick="loadJsonData()" class="form-btn">💾 লোড ডেটা</button>
            </div>

            <form id="invoiceForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                enctype="multipart/form-data">

                <div class="section-header">ভেন্ডর তথ্য</div>
                <div class="grid-3">
                    <div>
                        <label for="vendor_logo">ভেন্ডর লোগো (vendor_logo)</label>
                        <input type="file" id="vendor_logo" name="vendor_logo" accept="image/*">
                        <div id="logo_preview" class="logo-preview"></div>
                        <input type="hidden" id="existing_vendor_logo" name="existing_vendor_logo" value="">
                    </div>
                    <div>
                        <label for="vendor_title">ভেন্ডর নাম/উপাধি (vendor_title)</label>
                        <input type="text" id="vendor_title" name="vendor_title" required>
                    </div>
                    <div>
                        <label for="vendor_phone_no">ফোন নম্বর (vendor_phone_no)</label>
                        <input type="text" id="vendor_phone_no" name="vendor_phone_no">
                    </div>
                </div>
                <label for="vendor_address">ঠিকানা (vendor_address)</label>
                <input type="text" id="vendor_address" name="vendor_address" required>

                <div class="section-header">চালান ও তারিখ</div>
                <div class="grid-2">
                    <div>
                        <label for="invoice_no">চালান নম্বর (invoice_no)</label>
                        <input type="text" id="invoice_no" name="invoice_no" required>
                    </div>
                    <div>
                        <label for="date">তারিখ (date)</label>
                        <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="section-header">ক্লায়েন্ট তথ্য</div>
                <div class="grid-3">
                    <div>
                        <label for="client_title">ক্লায়েন্ট নাম/উপাধি (client_title)</label>
                        <input type="text" id="client_title" name="client_title" required>
                    </div>
                    <div>
                        <label for="client_phone_no">ফোন নম্বর (client_phone_no)</label>
                        <input type="text" id="client_phone_no" name="client_phone_no">
                    </div>
                    <div>
                        <label for="client_cc">কার্বন কপি (client_cc)</label>
                        <input type="text" id="client_cc" name="client_cc">
                    </div>
                </div>
                <label for="client_address">ঠিকানা (client_address)</label>
                <input type="text" id="client_address" name="client_address" required>


                <div class="section-header">কাজের বিবরণ</div>
                <div id="work_items">
                    <div class="work-item" data-index="0">
                        <div class="grid-3">
                            <div>
                                <label for="work_title_0">কাজের শিরোনাম (work_title)</label>
                                <input type="text" id="work_title_0" name="work_title[]" required>
                            </div>
                            <div>
                                <label for="work_qty_0">পরিমাণ (work_qty)</label>
                                <input type="number" id="work_qty_0" name="work_qty[]" min="1" value="1" required
                                    oninput="calculateAmount(0)">
                            </div>
                            <div>
                                <label for="work_rate_0">হার (work_rate)</label>
                                <input type="number" id="work_rate_0" name="work_rate[]" min="0" value="0" required
                                    oninput="calculateAmount(0)">
                            </div>
                        </div>
                        <label for="work_particular_0">বিস্তারিত বিবরণ (work_particular)</label>
                        <textarea id="work_particular_0" name="work_particular[]"></textarea>

                        <div style="text-align: right;">
                            <strong>মোট পরিমাণ: <span id="amount_display_0">0.00</span></strong>
                            <input type="hidden" id="amount_0" name="amount[]" value="0">
                            <button type="button" class="remove-btn" onclick="removeWorkItem(this)">মুছে ফেলুন</button>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addWorkItem()">➕ আরও কাজ যোগ করুন</button>


                <div class="section-header total-section">মোট হিসেব</div>
                <div class="grid-3">
                    <div>
                        <label>মোট চালান পরিমাণ (total_amount)</label>
                        <input type="text" id="total_amount_display" value="0.00" readonly>
                        <input type="hidden" id="total_amount" name="total_amount" value="0">
                    </div>
                    <div>
                        <label for="paid_amount">পরিশোধিত পরিমাণ (paid_amount)</label>
                        <input type="number" id="paid_amount" name="paid_amount" min="0" value="0"
                            oninput="calculateDueAmount()" required>
                    </div>
                    <div>
                        <label>বাকি পরিমাণ (due_amount)</label>
                        <input type="text" id="due_amount_display" value="0.00" readonly>
                        <input type="hidden" id="due_amount" name="due_amount" value="0">
                    </div>
                </div>
                <label for="amount_in_word">কথায় পরিমাণ (amount_in_word)</label>
                <input type="text" id="amount_in_word" name="amount_in_word">

                <div class="section-header">ভেন্ডর ব্যাংক ও MFS তথ্য</div>
                <div id="vendor_bank_details">
                    <div class="bank-item" data-index="0">
                        <div class="grid-2">
                            <div>
                                <label for="vendor_bank_0">ব্যাংকের নাম (vendor_bank)</label>
                                <input type="text" id="vendor_bank_0" name="vendor_bank[]">
                                <label for="vendor_bank_account_0">অ্যাকাউন্ট নম্বর (vendor_bank_account)</label>
                                <input type="text" id="vendor_bank_account_0" name="vendor_bank_account[]">
                            </div>
                            <div>
                                <label for="vendor_bank_branch_0">শাখার নাম (vendor_bank_branch)</label>
                                <input type="text" id="vendor_bank_branch_0" name="vendor_bank_branch[]">
                                <label for="vendor_bank_routing_0">রাউটিং নম্বর (vendor_bank_routing)</label>
                                <input type="text" id="vendor_bank_routing_0" name="vendor_bank_routing[]">
                            </div>
                        </div>

                        <label for="vendor_mfs_title_0">MFS সার্ভিস নাম (vendor_mfs_title)</label>
                        <input type="text" id="vendor_mfs_title_0" name="vendor_mfs_title[]">
                        <div class="grid-2">
                            <div>
                                <label for="vendor_mfs_type_0">MFS অ্যাকাউন্ট প্রকার (vendor_mfs_type)</label>
                                <input type="text" id="vendor_mfs_type_0" name="vendor_mfs_type[]">
                            </div>
                            <div>
                                <label for="vendor_mfs_account_0">MFS অ্যাকাউন্ট নম্বর (vendor_mfs_account)</label>
                                <input type="text" id="vendor_mfs_account_0" name="vendor_mfs_account[]">
                            </div>
                        </div>
                        <label for="vendor_amount_note_0">পরিমাণ সংক্রান্ত অতিরিক্ত নোট (vendor_amount_note)</label>
                        <input type="text" id="vendor_amount_note_0" name="vendor_amount_note[]">

                        <div style="text-align: right;">
                            <button type="button" class="remove-btn" onclick="removeBankItem(this)">মুছে ফেলুন</button>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addBankItem()">➕ আরও ব্যাংক/MFS তথ্য যোগ করুন</button>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="form-btn">✅ ফর্ম সাবমিট করুন ও প্রিভিউ দেখুন</button>
                </div>
            </form>
        </div>

        <script>
            let workItemCount = 1;
            let bankItemCount = 1;

            // --- কমন ফর্ম ফাংশন (unchanged) ---

            function getWorkItemHtml(index) {
                return `
                <div class="grid-3">
                    <div><label for="work_title_${index}">কাজের শিরোনাম</label><input type="text" id="work_title_${index}" name="work_title[]" required></div>
                    <div><label for="work_qty_${index}">পরিমাণ</label><input type="number" id="work_qty_${index}" name="work_qty[]" min="1" value="1" required oninput="calculateAmount(${index})"></div>
                    <div><label for="work_rate_${index}">হার</label><input type="number" id="work_rate_${index}" name="work_rate[]" min="0" value="0" required oninput="calculateAmount(${index})"></div>
                </div>
                <label for="work_particular_${index}">বিস্তারিত বিবরণ</label><textarea id="work_particular_${index}" name="work_particular[]"></textarea>
                <div style="text-align: right;">
                    <strong>মোট পরিমাণ: <span id="amount_display_${index}">0.00</span></strong>
                    <input type="hidden" id="amount_${index}" name="amount[]" value="0">
                    <button type="button" class="remove-btn" onclick="removeWorkItem(this)">মুছে ফেলুন</button>
                </div>
            `;
            }

            function addWorkItem() {
                const index = workItemCount++;
                const container = document.getElementById('work_items');
                const newItem = document.createElement('div');
                newItem.className = 'work-item';
                newItem.setAttribute('data-index', index);
                newItem.innerHTML = getWorkItemHtml(index);
                container.appendChild(newItem);
            }

            function removeWorkItem(button) {
                const item = button.closest('.work-item');
                item.remove();
                calculateTotalAmount();
            }

            function getBankItemHtml(index) {
                return `
                <div class="grid-2">
                    <div><label for="vendor_bank_${index}">ব্যাংকের নাম</label><input type="text" id="vendor_bank_${index}" name="vendor_bank[]">
                        <label for="vendor_bank_account_${index}">অ্যাকাউন্ট নম্বর</label><input type="text" id="vendor_bank_account_${index}" name="vendor_bank_account[]"></div>
                    <div><label for="vendor_bank_branch_${index}">শাখার নাম</label><input type="text" id="vendor_bank_branch_${index}" name="vendor_bank_branch[]">
                        <label for="vendor_bank_routing_${index}">রাউটিং নম্বর</label><input type="text" id="vendor_bank_routing_${index}" name="vendor_bank_routing[]"></div>
                </div>
                <label for="vendor_mfs_title_${index}">MFS সার্ভিস নাম</label><input type="text" id="vendor_mfs_title_${index}" name="vendor_mfs_title[]">
                <div class="grid-2">
                    <div><label for="vendor_mfs_type_${index}">MFS অ্যাকাউন্ট প্রকার</label><input type="text" id="vendor_mfs_type_${index}" name="vendor_mfs_type[]"></div>
                    <div><label for="vendor_mfs_account_${index}">MFS অ্যাকাউন্ট নম্বর</label><input type="text" id="vendor_mfs_account_${index}" name="vendor_mfs_account[]"></div>
                </div>
                <label for="vendor_amount_note_${index}">পরিমাণ সংক্রান্ত অতিরিক্ত নোট</label><input type="text" id="vendor_amount_note_${index}" name="vendor_amount_note[]">
                <div style="text-align: right;"><button type="button" class="remove-btn" onclick="removeBankItem(this)">মুছে ফেলুন</button></div>
            `;
            }

            function addBankItem() {
                const index = bankItemCount++;
                const container = document.getElementById('vendor_bank_details');
                const newItem = document.createElement('div');
                newItem.className = 'bank-item';
                newItem.setAttribute('data-index', index);
                newItem.innerHTML = getBankItemHtml(index);
                container.appendChild(newItem);
            }

            function removeBankItem(button) {
                button.closest('.bank-item').remove();
            }

            function calculateAmount(index) {
                const qty = parseFloat(document.getElementById(`work_qty_${index}`).value) || 0;
                const rate = parseFloat(document.getElementById(`work_rate_${index}`).value) || 0;
                const amount = (qty * rate).toFixed(2);

                document.getElementById(`amount_display_${index}`).textContent = amount;
                document.getElementById(`amount_${index}`).value = amount;
                calculateTotalAmount();
            }

            function calculateTotalAmount() {
                let total = 0;
                document.querySelectorAll('input[name="amount[]"]').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });

                const roundedTotal = total.toFixed(2);

                document.getElementById('total_amount_display').value = roundedTotal;
                document.getElementById('total_amount').value = roundedTotal;
                calculateDueAmount();
            }

            function calculateDueAmount() {
                const total = parseFloat(document.getElementById('total_amount').value) || 0;
                const paid = parseFloat(document.getElementById('paid_amount').value) || 0;
                const due = (total - paid).toFixed(2);

                document.getElementById('due_amount_display').value = due;
                document.getElementById('due_amount').value = due;
            }

            // --- JSON লোডিং ফাংশন (unchanged) ---

            async function loadJsonData() {
                const filename = document.getElementById('json_file_name').value.trim();
                if (!filename) {
                    alert("JSON ফাইলের নাম দিন।");
                    return;
                }

                try {
                    const response = await fetch(filename);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();

                    // সমস্ত অতিরিক্ত ওয়ার্ক ও ব্যাংক আইটেম মুছে ফেলা
                    document.getElementById('work_items').innerHTML = '';
                    document.getElementById('vendor_bank_details').innerHTML = '';
                    workItemCount = 0;
                    bankItemCount = 0;

                    // ১. সিঙ্গেল ভ্যালু ফিল্ডগুলো পূরণ করা
                    for (const key in data) {
                        const inputField = document.getElementById(key);
                        if (inputField && key !== 'work_items' && key !== 'bank_items' && key !== 'vendor_logo') {
                            inputField.value = data[key] || '';
                        }
                    }

                    // লোগো ফিল্ড হ্যান্ডলিং
                    const logoPreview = document.getElementById('logo_preview');
                    const existingLogoInput = document.getElementById('existing_vendor_logo');
                    if (data.vendor_logo && data.vendor_logo !== 'No File Selected') {
                        existingLogoInput.value = data.vendor_logo;
                        logoPreview.innerHTML = `**Existing Logo:** ${data.vendor_logo} (Upload new file to replace)`;
                    } else {
                        existingLogoInput.value = '';
                        logoPreview.innerHTML = ``;
                    }

                    // ২. কাজের আইটেমগুলো (work_items) পূরণ করা
                    if (data.work_items && Array.isArray(data.work_items)) {
                        data.work_items.forEach((item, index) => {
                            if (index === 0) {
                                document.getElementById('work_items').innerHTML = `<div class="work-item" data-index="0">${getWorkItemHtml(0)}</div>`;
                            } else {
                                addWorkItem();
                            }
                            const current_index = index;

                            document.getElementById(`work_title_${current_index}`).value = item.work_title || '';
                            document.getElementById(`work_particular_${current_index}`).value = item.work_particular || '';
                            document.getElementById(`work_qty_${current_index}`).value = item.work_qty || 0;
                            document.getElementById(`work_rate_${current_index}`).value = item.work_rate || 0;

                            calculateAmount(current_index);
                        });
                    }

                    // ৩. ব্যাংক আইটেমগুলো (bank_items) পূরণ করা
                    if (data.bank_items && Array.isArray(data.bank_items)) {
                        data.bank_items.forEach((item, index) => {
                            if (index === 0) {
                                document.getElementById('vendor_bank_details').innerHTML = `<div class="bank-item" data-index="0">${getBankItemHtml(0)}</div>`;
                            } else {
                                addBankItem();
                            }
                            const current_index = index;

                            document.getElementById(`vendor_bank_${current_index}`).value = item.vendor_bank || '';
                            document.getElementById(`vendor_bank_account_${current_index}`).value = item.vendor_bank_account || '';
                            document.getElementById(`vendor_bank_branch_${current_index}`).value = item.vendor_bank_branch || '';
                            document.getElementById(`vendor_bank_routing_${current_index}`).value = item.vendor_bank_routing || '';
                            document.getElementById(`vendor_mfs_title_${current_index}`).value = item.vendor_mfs_title || '';
                            document.getElementById(`vendor_mfs_type_${current_index}`).value = item.vendor_mfs_type || '';
                            document.getElementById(`vendor_mfs_account_${current_index}`).value = item.vendor_mfs_account || '';
                            document.getElementById(`vendor_amount_note_${current_index}`).value = item.vendor_amount_note || '';
                        });
                    }


                    // ৪. টোটাল এবং ডিউ অ্যামাউন্ট আপডেট করা
                    calculateTotalAmount();

                    alert("ডেটা সফলভাবে লোড হয়েছে! আপনি এখন সাবমিট করতে পারেন।");

                } catch (error) {
                    console.error('Error loading JSON:', error);
                    alert("JSON ফাইল লোড বা প্রসেস করা যায়নি। ফাইলের নাম ও পাথ চেক করুন। (" + filename + ")");
                }
            }

            // প্রাথমিক লোডের সময় ক্যালকুলেশন
            document.addEventListener('DOMContentLoaded', () => {
                // শুধুমাত্র ফর্মে গেলে ইনিশিয়ালাইজ করা হবে
                if (document.querySelector('.form-container')) {
                    if (document.getElementById('work_items').children.length === 0) {
                        document.getElementById('work_items').innerHTML = `<div class="work-item" data-index="0">${getWorkItemHtml(0)}</div>`;
                    }
                    if (document.getElementById('vendor_bank_details').children.length === 0) {
                        document.getElementById('vendor_bank_details').innerHTML = `<div class="bank-item" data-index="0">${getBankItemHtml(0)}</div>`;
                    }
                    calculateTotalAmount();
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>