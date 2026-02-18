<?php
$q = array();

$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';
$has_period = ($date_from !== '' && $date_to !== '');

$date_cond = '';
$date_paid_cond = '';
if ($has_period) {
	$d_from = mysql_res($date_from) . ' 00:00:00';
	$d_to   = mysql_res($date_to)   . ' 23:59:59';
	$date_cond = " AND created_at>='".$d_from."' AND created_at<='".$d_to."'";
	$date_paid_cond = " AND date_paid>='".$d_from."' AND date_paid<='".$d_to."'";
}

// Всего пользователей — за период или за всё время
$q['totals']['users'] = mysql_select(
	"SELECT COUNT(id) FROM users WHERE id>1 " . $date_cond,
	'string'
);

// Новые подписчики — уникальные пользователи, оплатившие подписку (за период или за всё время)
$q['totals']['new_subscribers'] = mysql_select(
	"SELECT COUNT(DISTINCT user_id) FROM subscription_orders WHERE paid=1 " . $date_paid_cond,
	'string'
);

// Количество подписок — оплаченные подписки (за период или за всё время)
$q['totals']['subscriptions'] = mysql_select(
	"SELECT COUNT(id) FROM subscription_orders WHERE paid=1 " . $date_paid_cond,
	'string'
);

// Неактивные пользователи — нет оплат в выбранном периоде (все пользователи минус те, кто платил)
$total_users = mysql_select("SELECT COUNT(id) FROM users WHERE id>1", 'string');
$q['totals']['inactive'] = max(0, (int)$total_users - (int)$q['totals']['new_subscribers']);

// Сумма оплат (за период или за всё время)
$sum = mysql_select(
	"SELECT COALESCE(SUM(sum_subscription), 0) FROM subscription_orders WHERE paid=1 " . $date_paid_cond,
	'string'
);
$q['totals']['sum'] = $sum !== '' ? number_format((float)$sum, 0, ',', ' ') : '0';

$q['users'] = mysql_select("
	SELECT *
	FROM users
	WHERE id>1
	ORDER BY created_at DESC
	LIMIT 5
", 'rows');

$q['last_orders'] = mysql_select("
	SELECT so.*, u.email
	FROM subscription_orders so
	LEFT JOIN users u ON u.id = so.user_id
	WHERE so.paid = 1
	ORDER BY so.date_paid DESC
	LIMIT 5
", 'rows');
?>

<style>
	.brands img {width: 50px}
</style>
<div class="row" style="margin-right:-30px; margin-left:-30px;">
	<div class="form-group col-xl-6 d-flex flex-wrap align-items-end gap-2">
		<div>
			<label class="form-label small mb-0">Период от</label>
			<input type="date" class="form-control" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>" style="min-width:160px;">
		</div>
		<div>
			<label class="form-label small mb-0">Период до</label>
			<input type="date" class="form-control" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>" style="min-width:160px;">
		</div>
		<button type="button" class="btn btn-primary mb-0" onclick="applyPeriod()">Показать</button>
	</div>
</div>

<script>
function applyPeriod() {
	var from = document.getElementById('date_from').value;
	var to = document.getElementById('date_to').value;
	var params = new URLSearchParams();
	if (from) params.set('date_from', from);
	if (to) params.set('date_to', to);
	top.location = 'admin.php?m=index' + (params.toString() ? '&' + params.toString() : '');
}
</script>

<?php $period_hint = $has_period ? 'в выбранный период' : 'за весь период'; ?>
<div class="row" style="margin-right:-30px; margin-left:-30px;">
	<div class="col-md-4 col-lg-2">
		<div class="card card-body">
			<h3 class="mb-3"><?= $q['totals']['users'] ?><small>Всего пользователей</small></h3>
			<p class="font-size-11 text-muted mb-0">Зарегистрировались <?= $period_hint ?></p>
		</div>
	</div>
	<div class="col-md-4 col-lg-2">
		<div class="card card-body">
			<h3 class="mb-3"><?= $q['totals']['new_subscribers'] ?><small>Новых подписчиков</small></h3>
			<p class="font-size-11 text-muted mb-0">Уникальных пользователей, оплативших <?= $period_hint ?></p>
		</div>
	</div>
	<div class="col-md-4 col-lg-2">
		<div class="card card-body">
			<h3 class="mb-3"><?= $q['totals']['subscriptions'] ?><small>Подписок</small></h3>
			<p class="font-size-11 text-muted mb-0">Оплаченных подписок <?= $period_hint ?></p>
		</div>
	</div>
	<div class="col-md-4 col-lg-2">
		<div class="card card-body">
			<h3 class="mb-3"><?= $q['totals']['inactive'] ?><small>Неактивные пользователи</small></h3>
			<p class="font-size-11 text-muted mb-0">Нет оплат подписки <?= $period_hint ?></p>
		</div>
	</div>
	<div class="col-md-4 col-lg-2">
		<div class="card card-body">
			<h3 class="mb-3"><?= $q['totals']['sum'] ?><small>Сумма (₽)</small></h3>
			<p class="font-size-11 text-muted mb-0">Оплаты <?= $period_hint ?></p>
		</div>
	</div>
</div>

<div class="row" style="margin-right:-30px; margin-left:-30px;">
	<div class="col-md-6">
		<div class="card card-body">
			<h6 class="card-title">5 последних оплат подписок</h6>
			<div class="list-group list-group-flush">
				<?php foreach ($q['last_orders'] as $v) { ?>
					<div class="list-group-item p-t-b-10 p-l-r-0 d-flex align-items-center justify-content-between">
						<div class="d-flex align-items-center"><?= htmlspecialchars(@$v['email'] ?: 'user #'.$v['user_id']) ?></div>
						<div class="d-flex align-items-center">
							<strong class="m-r-20"><?= number_format((float)$v['sum_subscription'], 0, ',', ' ') ?> ₽</strong>
							<span class="text-muted small"><?= $v['date_paid'] ? date('d.m.Y', strtotime($v['date_paid'])) : '' ?></span>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card card-body">
			<h6 class="card-title">5 последних пользователей</h6>
			<div class="list-group list-group-flush">
				<?php foreach ($q['users'] as $v) { ?>
					<div class="list-group-item p-t-b-10 p-l-r-0 d-flex align-items-center justify-content-between">
						<div class="d-flex align-items-center"><?= htmlspecialchars($v['email']) ?></div>
						<div class="d-flex align-items-center">
							<span class="text-muted small"><?= $v['created_at'] ? date('d.m.Y', strtotime($v['created_at'])) : '' ?></span>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
