<?php
/** Demo data so the CRM can be tried right away (set SEED_DEMO=0 / seed_demo=false to skip). */

function seed_img(string $name, callable $draw, int $w = 900, int $h = 900): ?string {
    if (!function_exists('imagecreatetruecolor')) return null;
    $rel = 'seed/' . $name . '.jpg';
    $abs = ROOT . '/uploads/' . $rel;
    if (!is_dir(dirname($abs))) @mkdir(dirname($abs), 0775, true);
    if (!is_file($abs)) {
        $im = imagecreatetruecolor($w, $h);
        $draw($im, $w, $h);
        imagejpeg($im, $abs, 82);
        imagedestroy($im);
    }
    return $rel;
}
function hexc($im, string $hex, int $alpha = 0) {
    [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
    return imagecolorallocatealpha($im, $r, $g, $b, $alpha);
}
function draw_marble(string $bg, string $vein, int $seed, int $veins = 9): callable {
    return function ($im, $w, $h) use ($bg, $vein, $seed, $veins) {
        mt_srand($seed);
        imagefilledrectangle($im, 0, 0, $w, $h, hexc($im, $bg));
        imagealphablending($im, true);
        for ($n = 0; $n < 900; $n++) { // soft cloudiness
            $c = hexc($im, $vein, 124); $x = mt_rand(0, $w); $y = mt_rand(0, $h); $r = mt_rand(30, 140);
            imagefilledellipse($im, $x, $y, $r, $r, $c);
        }
        for ($v = 0; $v < $veins; $v++) {
            $x = mt_rand(-200, $w); $y = mt_rand(0, $h); $ang = mt_rand(-60, 20) / 100;
            $thick = mt_rand(1, 4); $a = mt_rand(40, 95);
            for ($s = 0; $s < 420; $s++) {
                $ang += (mt_rand(-100, 100) / 1000);
                $nx = $x + cos($ang) * 5; $ny = $y + sin($ang) * 5;
                imagesetthickness($im, $thick);
                imageline($im, (int)$x, (int)$y, (int)$nx, (int)$ny, hexc($im, $vein, $a));
                $x = $nx; $y = $ny;
                if (mt_rand(0, 60) === 0) { $thick = max(1, $thick + mt_rand(-1, 1)); }
            }
        }
    };
}
function draw_wood(int $seed): callable {
    return function ($im, $w, $h) use ($seed) {
        mt_srand($seed);
        $planks = 5; $ph = $h / $planks;
        for ($p = 0; $p < $planks; $p++) {
            $base = [mt_rand(120, 150), mt_rand(78, 95), mt_rand(48, 60)];
            imagefilledrectangle($im, 0, (int)($p * $ph), $w, (int)(($p + 1) * $ph), imagecolorallocate($im, ...$base));
            for ($l = 0; $l < 70; $l++) {
                $y = $p * $ph + mt_rand(2, (int)$ph - 2); $d = mt_rand(-28, 18);
                $c = imagecolorallocatealpha($im, max(0, $base[0] + $d), max(0, $base[1] + $d), max(0, $base[2] + $d), mt_rand(40, 90));
                $x = 0; $yy = $y;
                while ($x < $w) { $nx = $x + 30; $ny = $yy + mt_rand(-2, 2); imageline($im, $x, (int)$yy, $nx, (int)$ny, $c); $x = $nx; $yy = $ny; }
            }
            imageline($im, 0, (int)(($p + 1) * $ph), $w, (int)(($p + 1) * $ph), imagecolorallocate($im, 70, 45, 28));
        }
    };
}
function draw_basin(string $bg, string $body): callable {
    return function ($im, $w, $h) use ($bg, $body) {
        imagefilledrectangle($im, 0, 0, $w, $h, hexc($im, $bg));
        imagefilledrectangle($im, 0, (int)($h * .66), $w, $h, hexc($im, '#c9b79c'));
        imagefilledellipse($im, (int)($w / 2), (int)($h * .62), (int)($w * .66), (int)($h * .18), hexc($im, '#00000', 100));
        imagefilledrectangle($im, (int)($w * .2), (int)($h * .42), (int)($w * .8), (int)($h * .62), hexc($im, $body));
        imagefilledellipse($im, (int)($w / 2), (int)($h * .62), (int)($w * .6), (int)($h * .12), hexc($im, $body));
        imagefilledellipse($im, (int)($w / 2), (int)($h * .42), (int)($w * .6), (int)($h * .12), hexc($im, '#3a3a3a'));
        imagefilledellipse($im, (int)($w / 2), (int)($h * .43), (int)($w * .52), (int)($h * .08), hexc($im, '#1d1d1d'));
        imagefilledrectangle($im, (int)($w * .49), (int)($h * .2), (int)($w * .51), (int)($h * .4), hexc($im, '#b9b9b9'));
        imagefilledrectangle($im, (int)($w * .44), (int)($h * .2), (int)($w * .51), (int)($h * .22), hexc($im, '#b9b9b9'));
    };
}
function draw_wc(): callable {
    return function ($im, $w, $h) {
        imagefilledrectangle($im, 0, 0, $w, $h, hexc($im, '#e9e6e1'));
        imagefilledrectangle($im, (int)($w * .3), (int)($h * .12), (int)($w * .7), (int)($h * .4), hexc($im, '#d4d0c9'));
        imagefilledellipse($im, (int)($w / 2), (int)($h * .55), (int)($w * .46), (int)($h * .5), hexc($im, '#fbfbfa'));
        imageellipse($im, (int)($w / 2), (int)($h * .55), (int)($w * .46), (int)($h * .5), hexc($im, '#cfcac2'));
        imagefilledrectangle($im, (int)($w * .27), (int)($h * .38), (int)($w * .73), (int)($h * .45), hexc($im, '#fbfbfa'));
    };
}
function draw_room(string $tile): callable {
    return function ($im, $w, $h) use ($tile) {
        imagefilledrectangle($im, 0, 0, $w, $h, hexc($im, '#d9d2c6'));
        $src = @imagecreatefromjpeg(ROOT . '/uploads/' . $tile);
        $horizon = (int)($h * .55);
        if ($src) {
            for ($y = $horizon; $y < $h; $y++) {
                $t = ($y - $horizon) / ($h - $horizon); $scale = 0.35 + 0.65 * $t;
                $sy = (int)(imagesy($src) * $t * 0.99);
                imagecopyresampled($im, $src, (int)(($w - $w * (1 + $scale)) / 2), $y, 0, $sy, (int)($w * (1 + $scale)), 1, imagesx($src), 1);
            }
            imagedestroy($src);
        }
        imagefilledrectangle($im, (int)($w * .18), (int)($h * .42), (int)($w * .62), (int)($h * .62), hexc($im, '#6f6a62'));
        imagefilledrectangle($im, (int)($w * .16), (int)($h * .5), (int)($w * .64), (int)($h * .66), hexc($im, '#7d776e'));
        imagefilledrectangle($im, (int)($w * .72), (int)($h * .18), (int)($w * .86), (int)($h * .4), hexc($im, '#c6a46a'));
        imagefilledellipse($im, (int)($w * .8), (int)($h * .75), (int)($w * .22), (int)($h * .05), hexc($im, '#000000', 90));
    };
}

function seed_demo(): void {
    $cat = []; foreach (rows('SELECT * FROM categories') as $c) $cat[$c['grp'] . ':' . $c['name']] = $c['id'];
    $role = []; foreach (rows('SELECT * FROM roles') as $r) $role[$r['name']] = $r['id'];

    $img = [
        'calacatta' => seed_img('calacatta', draw_marble('#f3efe8', '#b58b4c', 11, 8)),
        'calacatta2' => seed_img('calacatta-2', draw_marble('#f5f2ec', '#a8834f', 12, 6)),
        'statuario' => seed_img('statuario', draw_marble('#f6f6f4', '#8d8d8d', 21, 7)),
        'teak' => seed_img('teak', draw_wood(31)),
        'nero' => seed_img('nero', draw_marble('#151515', '#c9a45c', 41, 9)),
        'crema' => seed_img('crema', draw_marble('#e9dcc4', '#b59a6f', 51, 6)),
        'verde' => seed_img('verde', draw_marble('#a9b8ad', '#eef2ee', 61, 10)),
        'basin' => seed_img('basin', draw_basin('#ece8e2', '#2b2b2b')),
        'wc' => seed_img('wc', draw_wc()),
        'mixer' => seed_img('mixer', draw_basin('#e5e9ec', '#cfd3d6')),
    ];
    $img['room_calacatta'] = $img['calacatta'] ? seed_img('room-calacatta', draw_room($img['calacatta']), 1200, 800) : null;
    $img['room_teak'] = $img['teak'] ? seed_img('room-teak', draw_room($img['teak']), 1200, 800) : null;

    $tileFeat = ['Stain resistant', 'Easy to clean', 'Rectified edges', 'Scratch resistant'];
    $products = [
        ['AC-1001', 'Calacatta Gold Glossy', 'tiles:GVT', '600×1200 mm', 'Glossy', '9 mm', 2, 15.5, 112, 98, 86, 90, 72, 'calacatta', ['calacatta', 'room_calacatta'], [['Godown A', 'R-12', 'B-24 · Shade 3', 220], ['Showroom', 'Display D-1', 'B-24 · Shade 3', 18]], 300, 'printed'],
        ['AC-1002', 'Statuario Bianco', 'tiles:PGVT', '800×1600 mm', 'High gloss', '9 mm', 2, 27.55, 165, 145, 128, 124, 105, 'statuario', ['statuario'], [['Godown A', 'R-04', 'S-11 · Shade 1', 84], ['Showroom', 'Display D-3', 'S-11 · Shade 1', 12]], 0, 'printed'],
        ['AC-1003', 'Teak Plank Wood', 'tiles:Wooden', '200×1200 mm', 'Matt', '9 mm', 6, 15.5, 86, 74, 66, 64, 52, 'teak', ['teak', 'room_teak'], [['Godown B', 'R-02', 'W-07 · Shade 2', 390], ['Showroom', 'Display D-6', 'W-07 · Shade 2', 20]], 0, 'generated'],
        ['AC-1004', 'Nero Marquina Gold', 'tiles:GVT', '600×1200 mm', 'Glossy', '9 mm', 2, 15.5, 132, 115, 102, 98, 82, 'nero', ['nero'], [['Godown A', 'R-15', 'N-03 · Shade 1', 34]], 0, 'printed'],
        ['AC-1005', 'Crema Onyx', 'tiles:GVT', '600×1200 mm', 'Glossy', '9 mm', 2, 15.5, 118, 102, 92, 88, 70, 'crema', ['crema'], [['Godown A', 'R-08', 'C-12 · Shade 2', 160]], 0, 'printed'],
        ['AC-1006', 'Verde Alpi', 'tiles:PGVT', '600×1200 mm', 'High gloss', '9 mm', 2, 15.5, 140, 122, 108, 104, 88, 'verde', ['verde'], [['Godown B', 'R-11', 'V-02 · Shade 1', 72]], 0, 'generated'],
        ['AC-1011', 'Calacatta Gold Glossy 800×1600', 'tiles:PGVT', '800×1600 mm', 'Glossy', '9 mm', 2, 27.55, 158, 138, 122, 118, 98, 'calacatta', ['calacatta2'], [['Godown A', 'R-13', 'B-31 · Shade 2', 64]], 0, 'printed'],
        ['AC-1021', 'Calacatta Gold Matt 600×600', 'tiles:GVT', '600×600 mm', 'Matt', '9 mm', 4, 15.5, 72, 62, 55, 53, 41, 'calacatta', ['calacatta2'], [['Godown B', 'R-05', 'B-19 · Shade 1', 140]], 0, 'printed'],
        ['AC-2101', 'Designer Table-top Basin', 'sanitary:Basin', '600×400 mm', 'Matt black', '', null, null, 7900, 6450, 5800, 5600, 4400, null, ['basin'], [['Showroom', 'Display S-2', '', 4], ['Godown C', 'R-01', '', 10]], 0, 'printed'],
        ['AC-2201', 'Wall-hung WC with Soft-close Seat', 'sanitary:WC', '520×360 mm', 'Glossy white', '', null, null, 17500, 14800, 13200, 12800, 10500, null, ['wc'], [['Godown C', 'R-03', '', 22]], 0, 'printed'],
        ['AC-3101', 'Single-lever Basin Mixer', 'fittings:Faucet', 'Standard', 'Chrome', '', null, null, 4200, 3450, 3050, 2950, 2300, null, ['mixer'], [['Showroom', 'Display F-1', '', 6], ['Godown C', 'R-07', '', 30]], 0, 'generated'],
    ];
    $pid = [];
    foreach ($products as $p) {
        [$code, $name, $ck, $size, $finish, $thk, $pcs, $cov, $mrp, $offer, $dealer, $min, $cost, $look, $imgs, $stock, $incoming, $qr] = $p;
        $grp = explode(':', $ck)[0];
        $id = insert('products', [
            'code' => $code, 'code_key' => code_key($code), 'name' => $name, 'category_id' => $cat[$ck] ?? null,
            'size' => $size, 'finish' => $finish, 'thickness' => $thk ?: null, 'pcs_per_box' => $pcs, 'coverage_per_box' => $cov,
            'weight_per_box' => $grp === 'tiles' ? ($cov > 20 ? '41 kg' : '28 kg') : null,
            'where_to_use' => $grp === 'tiles' ? ($look === 'teak' ? 'Floor · Living, bedroom, balcony' : 'Floor · Wall · Living, bedroom') : ($grp === 'sanitary' ? 'Bathroom · Powder room' : 'Bathroom'),
            'features' => json_encode($grp === 'tiles' ? $tileFeat : ($grp === 'sanitary' ? ['Easy to clean', 'Premium glaze', '10-year warranty'] : ['Ceramic cartridge', '5-year warranty'])),
            'description' => null, 'unit' => $grp === 'tiles' ? 'sqft' : 'pc',
            'mrp' => $mrp, 'offer_price' => $offer, 'dealer_price' => $dealer, 'min_price' => $min, 'purchase_cost' => $cost, 'gst' => 18,
            'look_group' => $look, 'qr_status' => $qr,
            'visibility' => $code === 'AC-1004' ? json_encode(['customer' => ['mrp' => 0]]) : null,
        ]);
        $pid[$code] = $id;
        foreach ($imgs as $i => $k) if (!empty($img[$k])) insert('product_images', ['product_id' => $id, 'path' => $img[$k], 'kind' => str_starts_with($k, 'room') ? 'room' : 'photo', 'sort' => $i]);
        foreach ($stock as [$loc, $rack, $batch, $qty]) insert('product_stock', ['product_id' => $id, 'location' => $loc, 'rack' => $rack, 'batch' => $batch ?: null, 'qty' => $qty]);
        if ($incoming) insert('product_stock', ['product_id' => $id, 'location' => 'Incoming (PO-0912)', 'qty' => $incoming, 'incoming' => 1, 'eta' => date('Y-m-d', strtotime('+6 days'))]);
    }

    $pin = password_hash('1234', PASSWORD_DEFAULT);
    $st = [];
    foreach ([
        ['Rahul Patel', '9800011201', 'Sales executive', 'active'], ['Kiran Shah', '9800011202', 'Senior sales', 'active'],
        ['Meena Desai', '9800011203', 'Sales executive', 'active'], ['Jignesh Patel', '9800011204', 'Godown', 'active'],
        ['Bhavin', '9800011205', 'Sales trainee', 'blocked'],
    ] as [$n, $m, $r, $s]) $st[$n] = insert('staff', ['name' => $n, 'mobile' => $m, 'pin_hash' => $pin, 'role_id' => $role[$r], 'showroom' => 'Showroom · Chikhli', 'status' => $s]);

    // scans over the last 7 days
    mt_srand(7);
    $weights = ['AC-1001' => 30, 'AC-1002' => 22, 'AC-1003' => 18, 'AC-1004' => 12, 'AC-2101' => 9, 'AC-2201' => 6, 'AC-1005' => 5, 'AC-1006' => 4, 'AC-1011' => 3, 'AC-1021' => 3, 'AC-3101' => 3];
    $bag = []; foreach ($weights as $c => $w) for ($i = 0; $i < $w; $i++) $bag[] = $pid[$c];
    $staffIds = [$st['Rahul Patel'], $st['Rahul Patel'], $st['Kiran Shah'], $st['Kiran Shah'], $st['Meena Desai'], $st['Jignesh Patel']];
    $vals = [];
    foreach ([[6, 70, 40], [5, 95, 52], [4, 80, 44], [3, 110, 60], [2, 150, 85], [1, 160, 92], [0, 121, 63]] as [$daysAgo, $staffN, $custN]) {
        $day = date('Y-m-d', strtotime("-$daysAgo days"));
        $maxH = $daysAgo === 0 ? max(10, min(19, (int)date('G'))) : 19;
        for ($i = 0; $i < $staffN + $custN; $i++) {
            $t = sprintf('%s %02d:%02d:%02d', $day, mt_rand(10, $maxH), mt_rand(0, 59), mt_rand(0, 59));
            if ($daysAgo === 0 && strtotime($t) > time()) $t = date('Y-m-d H:i:s', time() - mt_rand(60, 3600));
            $isStaff = $i < $staffN;
            $vals[] = [$bag[array_rand($bag)], $isStaff ? 'staff' : 'customer', $isStaff ? $staffIds[array_rand($staffIds)] : null, $t];
        }
    }
    foreach (array_chunk($vals, 200) as $chunk) {
        $sql = 'INSERT INTO scans (product_id, type, staff_id, created_at) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?,?)'));
        q($sql, array_merge(...$chunk));
    }

    // customers + quotations
    $cust = function (string $name, string $mobile, string $city, string $source = 'quotation', string $addr = null) {
        return insert('customers', ['name' => $name, 'mobile' => $mobile, 'city' => $city, 'source' => $source, 'address' => $addr]);
    };
    $ym = date('ym');
    $mk = function (int $n, string $staff, int $custId, string $status, array $items, string $when, ?string $follow = null, string $addr = null) use ($ym, $st, $pid) {
        $qid = insert('quotations', [
            'number' => "QT-$ym-" . str_pad((string)$n, 4, '0', STR_PAD_LEFT), 'share_token' => bin2hex(random_bytes(12)),
            'staff_id' => $st[$staff], 'customer_id' => $custId, 'status' => $status, 'delivery_address' => $addr,
            'delivery' => 'Required · transport extra', 'follow_up_at' => $follow, 'valid_till' => date('Y-m-d', strtotime($when . ' +7 days')),
            'created_at' => $when, 'updated_at' => $when,
        ]);
        foreach ($items as $k => $it) {
            [$code, $qty, $rate] = $it;
            $p = product_by_id($pid[$code]);
            if ($p['unit'] === 'pc') { $area = 0; $amount = $qty * $rate; }
            else { $area = round($qty * $p['coverage_per_box'], 2); $amount = round($area * $rate, 2); }
            insert('quotation_items', [
                'quotation_id' => $qid, 'product_id' => $p['id'], 'boxes' => $qty, 'area' => $area, 'rate' => $rate, 'amount' => $amount, 'gst' => $p['gst'],
                'discount_pct' => round(max(0, ($p['offer_price'] - $rate) / $p['offer_price'] * 100), 2), 'note' => $it[3] ?? null,
                'approval' => $it[4] ?? 'none', 'approval_reason' => $it[5] ?? null, 'sort' => $k,
            ]);
        }
        recalc_quote($qid);
        return $qid;
    };
    $now = date('Y-m-d');
    $nisha = $cust('Nisha Desai', '9800045120', 'Chikhli', 'quotation', 'Plot 14, Shivam Society, Chikhli');
    $mehta = $cust('Mehta Builders', '9700022310', 'Navsari');
    $ankit = $cust('Ankit Joshi', '9825044120', 'Bilimora');
    $rakesh = $cust('Rakesh Patel', '9909012345', 'Chikhli');
    $komal = $cust('Komal Shah', '9879011223', 'Navsari');

    $mk(172, 'Rahul Patel', $nisha, 'sent', [['AC-1001', 42, 95, 'Living + dining floor'], ['AC-1003', 20, 72], ['AC-2101', 2, 6450], ['AC-2201', 2, 14800]], "$now 14:10:00", date('Y-m-d 11:00:00', strtotime('+2 days')), 'Plot 14, Shivam Society, Chikhli');
    $mk(171, 'Kiran Shah', $mehta, 'approval', [['AC-1002', 44, 118, 'Bulk builder order · 1,200 sq.ft', 'pending', 'Bulk builder order'], ['AC-1001', 60, 94], ['AC-1003', 40, 70], ['AC-2201', 6, 14200], ['AC-2101', 6, 6200], ['AC-3101', 12, 3300], ['AC-1004', 10, 112], ['AC-1005', 20, 99], ['AC-1021', 30, 60]], "$now 13:22:00");
    $mk(170, 'Meena Desai', $ankit, 'draft', [['AC-1005', 18, 100], ['AC-3101', 2, 3450], ['AC-2101', 1, 6450]], "$now 12:05:00");
    $mk(168, 'Rahul Patel', $rakesh, 'won', [['AC-1003', 26, 72], ['AC-2201', 1, 14800]], date('Y-m-d 16:40:00', strtotime('-1 day')));
    $mk(161, 'Kiran Shah', $komal, 'lost', [['AC-1004', 20, 110], ['AC-1002', 6, 140], ['AC-2101', 1, 6450], ['AC-2201', 1, 14800], ['AC-3101', 2, 3450]], date('Y-m-d 11:15:00', strtotime('-2 days')));

    // older quotations for the month's stats
    $names = ['Patel Residency', 'Hetal Mistry', 'Suresh Naik', 'Dhaval Shah', 'Sunrise Homes', 'Arjun Desai', 'Bhoomi Patel', 'Vikram Rana', 'Pooja Solanki', 'Shree Infra', 'Manish Modi', 'Kavita Joshi'];
    $sts = ['won', 'won', 'lost', 'sent', 'won', 'lost', 'sent', 'won', 'won', 'lost', 'sent', 'won'];
    $codes = array_keys($weights);
    foreach ($names as $i => $n) {
        $cid = $cust($n, '98' . str_pad((string)(25000000 + $i * 7919), 8, '0', STR_PAD_LEFT), ['Chikhli', 'Navsari', 'Bilimora', 'Valsad'][$i % 4]);
        $items = [];
        foreach (array_rand(array_flip($codes), mt_rand(2, 4)) as $c) {
            $p = product_by_id($pid[$c]);
            $items[] = [$c, $p['unit'] === 'pc' ? mt_rand(1, 4) : mt_rand(12, 60), round($p['offer_price'] * (1 - mt_rand(0, 4) / 100))];
        }
        $mk(140 + $i, array_keys($st)[$i % 3], $cid, $sts[$i], $items, date('Y-m-d H:i:s', strtotime('-' . (3 + $i) . ' days 1' . ($i % 7) . ':00')));
    }

    // leads from customer scans
    $l1 = $cust('', '9876500011', '', 'qr_whatsapp');
    insert('leads', ['customer_id' => $l1, 'product_id' => $pid['AC-1001'], 'channel' => 'whatsapp', 'note' => 'Scanned 3 products', 'created_at' => date('Y-m-d H:i:s', strtotime('-40 minutes'))]);
    $l2 = $cust('Priya Naik', '7000012345', 'Navsari', 'qr_callback');
    insert('leads', ['customer_id' => $l2, 'product_id' => $pid['AC-2101'], 'channel' => 'callback', 'area' => '500 sq.ft', 'callback_at' => date('Y-m-d 17:00:00'), 'note' => 'Call back 5 PM']);
    $l3 = $cust('Ketan Mehta', '9712345678', 'Chikhli', 'qr_call');
    insert('leads', ['customer_id' => $l3, 'product_id' => $pid['AC-1003'], 'channel' => 'visit', 'status' => 'contacted', 'note' => 'Will visit showroom Saturday', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);

    q('UPDATE products SET reprint_requested = 1 WHERE code IN ("AC-1004","AC-1011","AC-2201")');
}
