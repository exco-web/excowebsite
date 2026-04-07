<?php
$items = [
    ['text' => '20+ Years Experience',        'icon' => 'fa-history'],
    ['text' => 'Trusted by 200+ Clients',      'icon' => 'fa-users'],
    ['text' => 'Expert Tax Planning',          'icon' => 'fa-calculator'],
    ['text' => 'Fully Accredited',             'icon' => 'fa-certificate'],
    ['text' => 'Free Initial Consultation',    'icon' => 'fa-comments'],
    ['text' => 'Tailored Financial Strategy',  'icon' => 'fa-chart-line'],
    ['text' => 'Confidential & Secure',        'icon' => 'fa-lock'],
    ['text' => 'Proven Results',               'icon' => 'fa-trophy'],
    ['text' => 'Dedicated Account Manager',    'icon' => 'fa-handshake'],
    ['text' => 'Fast Turnaround',              'icon' => 'fa-bolt'],
];
?>
<div class='scroll' id='scroll-one'>
    <?php foreach ($items as $item): ?>
        <div class='item'>
            <?= htmlspecialchars($item['text']) ?>
            <i class="fas <?= $item['icon'] ?>"></i>
        </div>
    <?php endforeach; ?>
</div>
<div class='scroll' id='scroll-two'>
    <?php foreach ($items as $item): ?>
        <div class='item'>
            <?= htmlspecialchars($item['text']) ?>
            <i class="fas <?= $item['icon'] ?>"></i>
        </div>
    <?php endforeach; ?>
</div>
<div class='fade'></div>
