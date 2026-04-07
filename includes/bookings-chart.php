<?php
// Fetch booking counts per day for the next 7 days
$chart_data = [];
for ($i = 0; $i < 7; $i++) {
    $chart_data[date('Y-m-d', strtotime("+$i days"))] = 0;
}

$stmt = mysqli_prepare($con,
    "SELECT date, COUNT(*) AS count
     FROM bookings
     WHERE date >= CURDATE() AND date < CURDATE() + INTERVAL 7 DAY
     GROUP BY date"
);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    if (isset($chart_data[$row['date']])) {
        $chart_data[$row['date']] = (int)$row['count'];
    }
}

$max = 20;
?>

<div class="chart__wrap">
    <h2 class="chart__title">Bookings — Next 7 Days</h2>
    <div class="chart__bars">
        <?php foreach ($chart_data as $date => $count): ?>
            <?php $pct = round(($count / $max) * 100); ?>
            <div class="chart__col">
                <span class="chart__count"><?= $count ?: '' ?></span>
                <div class="chart__bar" style="--pct: <?= $pct ?>%"></div>
                <span class="chart__label">
                    <span class="chart__day"><?= date('D', strtotime($date)) ?></span>
                    <span class="chart__date"><?= date('j M', strtotime($date)) ?></span>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
