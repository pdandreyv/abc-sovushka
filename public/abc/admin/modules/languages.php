<?php

/*
 * v1.4.14 - event_func
 * v1.4.16 - $delete удалил confirm
 * v1.4.17 - сокращение параметров form
 * v1.4.20 - значение в поле
 * v1.4.45 - карты и многоуровневый словарь
 */

$locales = array(
	'en'	=>	'Английский',
	'ar'	=>	'Арабский',
	'bg'	=>	'Болгарский',
	'ca'	=>	'Каталанский',
	'cn'	=>	'Китайский',
	'cs'	=>	'Чешский',
	'da'	=>	'Датский',
	'de'	=>	'Немецкий',
	'el'	=>	'Греческий',
	'es'	=>	'Испанский',
	'eu'	=>	'Баскский',
	'fa'	=>	'Фарси',
	'fi'	=>	'Финский',
	'fr'	=>	'Французский',
	'he'	=>	'Иврит',
	'hu'	=>	'Венгерский',
	'it'	=>	'Итальянский',
	'ja'	=>	'Японский',
	'kk'	=>	'Казахский',
	'lt'	=>	'Литовский',
	'lv'	=>	'Латышский',
	'nl'	=>	'Голландский',
	'no'	=>	'Норвежский',
	'pl'	=>	'Польский',
	'ptbr'	=>	'Португальский (Бразилия)',
	'ptpt'	=>	'Португальский',
	'ro'	=>	'Румынский',
	'ru'	=>	'Русский',
	'si'	=>	'Словенский',
	'sk'	=>	'Словацкий',
	'sl'	=>	'Словенский',
	'sr'	=>	'Сербский',
	'th'	=>	'Таиландский',
	'tr'	=>	'Турецкий',
	'tw'	=>	'Тайванский',
	'ua'	=>	'Украинский',
	'vi'	=>	'Вьетнамский',
);

//удаление языка
function event_delete_languages ($q) {
	global $config;
	if ($q) {
		foreach ($config['lang_tables'] as $key => $val) {
			foreach ($val as $k => $v) {
				mysql_fn('query', "ALTER TABLE `" . $key . "` DROP `" . $k . $q['id'] . "`");
			}
		}
	}
}

function event_change_languages ($q) {
	global $config;
	if (is_dir(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary') || mkdir(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary', 0755, true)) {
		$post = stripslashes_smart($_POST);
		if (@$post['dictionary']) foreach ($post['dictionary'] as $key => $val) {
			$str = '<?php' . PHP_EOL;
			$str .= '$lang[\'' . $key . '\'] = array(' . PHP_EOL;
			foreach ($val as $k => $v) {
				//v1.4.45 - для многоуровневого словаря
				if (is_array($v)) $v = serialize($v);
				$str .= "	'" . $k . "'=>'" . str_replace("'", "\'", $v) . "'," . PHP_EOL;
			}
			$str .= ');';
			$str .= '?>';
			$fp = fopen(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary/' . $key . '.php', 'w');
			fwrite($fp, $str);
			fclose($fp);
		}
	}
	//если мультиязычный то нужно добавлять колонки в мультиязычные таблицы
	if ($config['multilingual']) {
		if ($_GET['id'] == 'new') {
			foreach ($config['lang_tables'] as $key=>$val) {
				foreach ($val as $k=>$v) {
					mysql_fn('query',"ALTER TABLE `".$key."` ADD `".$k.$q['id']."` ".$v." AFTER `".$k."`");
				}
			}
		}
	}
}

//многоязычный
if ($config['multilingual']) {
	$module['save_as'] = true;
	$table = array(
		'id'			=>	'rank:desc name id',
		'name'			=>	'',
		'rank'			=>	'',
		'url'			=>	'',
		'localization'	=>	$locales,
		'display'		=>	'display'
	);
	$form[0][] = array('input td4','name');
	$form[0][] = array('input td2','rank');
	$form[0][] = array('input td2','url');
	$form[0][] = array('select td2','localization',array('value'=>array(true,$locales)));
	$form[0][] = array('checkbox td2','display');
}
//одноязычный
else {
	$module['one_form'] = true;
	$get['id'] = 1;
	if ($get['u']!='edit') {
		$post = mysql_select("
			SELECT *
			FROM languages
			WHERE id = 1
			LIMIT 1
		",'row');
	}
}

$a18n['localization'] = 'localization';

//исключения
if ($get['u']=='edit') {
	unset($post['dictionary']);
}
else {
	if ($get['id']>0) {
		$root = ROOT_DIR . 'files/languages/' . $get['id'] . '/dictionary';
		if (is_dir($root) && $handle = opendir($root)) {
			while (false !== ($file = readdir($handle))) {
				if (strlen($file) > 2)
					include(ROOT_DIR . 'files/languages/' . $get['id'] . '/dictionary/' . $file);
			}
		}
	}
}

//v1.4.16 - $delete удалил confirm
$delete = array('pages'=>'language');

//вкладки
$tabs = array(
	0 => 'Главная',
	1 => 'ЛК: меню',
	2 => 'ЛК: Личные данные',
	3 => 'ЛК: Подписки',
	4 => 'ЛК: Кладовая идей'
);

$form[0][] = lang_form('input td12','lk_dashboard|page_title','Личный кабинет — Совушкина школа');
$form[0][] = lang_form('input td12','lk_dashboard|breadcrumbs','Главная / Кабинет');
$form[0][] = lang_form('input td12','lk_dashboard|status','Осталось 5 дней подписки: продлить / отменить');
$form[0][] = lang_form('input td12','lk_dashboard|welcome','Добро пожаловать');
$form[0][] = lang_form('input td6','lk_dashboard|card_subscription_title','Моя подписка');
$form[0][] = lang_form('input td6','lk_dashboard|card_subscription_text','Вы подписаны на материалы для 1 класса');
$form[0][] = lang_form('input td6','lk_dashboard|card_ideas_title','Кладовая идей');
$form[0][] = lang_form('input td6','lk_dashboard|card_ideas_text','Материалы доступны всем пользователям');
$form[0][] = lang_form('input td6','lk_dashboard|card_portfolio_title','Портфолио');
$form[0][] = lang_form('input td6','lk_dashboard|card_portfolio_text','Ваши награды и сертификаты');
$form[0][] = lang_form('input td6','lk_dashboard|status_left','Осталось');
$form[0][] = lang_form('input td2','lk_dashboard|days_1','день');
$form[0][] = lang_form('input td2','lk_dashboard|days_2','дня');
$form[0][] = lang_form('input td2','lk_dashboard|days_5','дней');
$form[0][] = lang_form('input td6','lk_dashboard|status_subscription','подписки: продлить / отменить');
$form[0][] = lang_form('input td6','lk_dashboard|status_none','Подписок нет: выбрать / оформить');
$form[0][] = lang_form('input td6','lk_dashboard|card_subscriptions_title','Мои подписки');
$form[0][] = lang_form('input td6','lk_dashboard|card_subscriptions_list','Вы подписаны на:');
$form[0][] = lang_form('input td6','lk_dashboard|card_subscriptions_empty','Пока нет активных подписок.');
$form[0][] = lang_form('input td6','lk_dashboard|card_ideas_latest','Последний материал:');
$form[0][] = lang_form('input td6','lk_dashboard|card_ideas_link','Перейти в раздел');
$form[0][] = '<h2>Авторизация / Регистрация</h2>';
$form[0][] = lang_form('input td12','auth|welcome_title','Добро пожаловать в Совушкину школу!');
$form[0][] = lang_form('textarea td12','auth|welcome_text','Готовые уроки, рабочие листы, презентации и бонусные материалы для учителей начальных классов, воспитателей и педагогов дополнительного образования. Доступ 24/7 — экономьте время и работайте с удовольствием!');
$form[0][] = lang_form('input td6','auth|tab_login','Вход');
$form[0][] = lang_form('input td6','auth|tab_register','Регистрация');
$form[0][] = lang_form('input td12','auth|login_heading','Вход в личный кабинет');
$form[0][] = lang_form('input td6','auth|login_email_placeholder','Email');
$form[0][] = lang_form('input td6','auth|login_password_placeholder','Пароль');
$form[0][] = lang_form('input td6','auth|login_button','Войти');
$form[0][] = lang_form('input td6','auth|social_title','или через соцсети:');
$form[0][] = lang_form('input td12','common|support_vk_url','Ссылка на поддержку (ВК)');
$form[0][] = lang_form('input td12','auth|register_heading','Регистрация');
$form[0][] = lang_form('input td6','auth|register_last_name','Фамилия');
$form[0][] = lang_form('input td6','auth|register_first_name','Имя');
$form[0][] = lang_form('input td6','auth|register_middle_name','Отчество');
$form[0][] = lang_form('input td6','auth|register_email','Email');
$form[0][] = lang_form('input td6','auth|register_password','Пароль');
$form[0][] = lang_form('input td6','auth|register_password_confirm','Повторите пароль');
$form[0][] = lang_form('input td6','auth|register_button','Зарегистрироваться');
$form[0][] = lang_form('textarea td12','auth|register_consent','Регистрируясь, я принимаю условия Пользовательского соглашения об использовании Личного кабинета Клиента и соглашаюсь с Политикой обработки персональных данных.');

$form[1][] = lang_form('input td6','lk_menu|home','Главная');
$form[1][] = lang_form('input td6','lk_menu|profile','Личные данные');
$form[1][] = lang_form('input td6','lk_menu|portfolio','Портфолио');
$form[1][] = lang_form('input td6','lk_menu|my_subscriptions','Мои подписки');
$form[1][] = lang_form('input td6','lk_menu|subscriptions','Оформить подписку');
$form[1][] = lang_form('input td6','lk_menu|ideas','Кладовая идей');
$form[1][] = lang_form('input td6','lk_menu|logout','Выйти');

$form[2][] = lang_form('input td12','lk_profile|page_title','Личные данные — Совушкина школа');
$form[2][] = lang_form('input td12','lk_profile|breadcrumbs','Главная / Кабинет / Личные данные');
$form[2][] = lang_form('input td12','lk_profile|status','Подписок нет: выбрать / оформить');
$form[2][] = lang_form('input td12','lk_profile|heading','Проверьте и сохраните данные профиля');
$form[2][] = lang_form('textarea td12','lk_profile|hint','Мы создали для вас личный кабинет. Пожалуйста, проверьте данные, добавьте (при желании) дополнительную информацию и нажмите «Сохранить». После сохранения вы сможете редактировать профиль в любой момент.');
$form[2][] = lang_form('input td6','lk_profile|card_title','Личные данные');
$form[2][] = lang_form('input td6','lk_profile|edit','Редактировать');
$form[2][] = lang_form('input td6','lk_profile|save','Сохранить');
$form[2][] = lang_form('input td6','lk_profile|saved','Данные сохранены');
$form[2][] = lang_form('input td6','lk_profile|error','Ошибка');
$form[2][] = lang_form('input td6','lk_profile|validation_title','Ошибки валидации:');
$form[2][] = lang_form('input td6','lk_profile|security_title','Безопасность');
$form[2][] = lang_form('input td6','lk_profile|change_password','Сменить пароль');
$form[2][] = lang_form('input td6','lk_profile|password','Пароль');
$form[2][] = lang_form('input td6','lk_profile|current_password','Текущий пароль');
$form[2][] = lang_form('input td6','lk_profile|new_password','Новый пароль');
$form[2][] = lang_form('input td6','lk_profile|repeat_password','Повторите новый пароль');
$form[2][] = lang_form('input td6','lk_profile|save_password','Сохранить пароль');
$form[2][] = lang_form('input td6','lk_profile|subscriptions_title','Подписки');
$form[2][] = lang_form('textarea td12','lk_profile|subscriptions_empty','У вас пока нет активных подписок. Выберите класс или направление — материалы откроются сразу после оформления.');
$form[2][] = lang_form('input td6','lk_profile|subscriptions_cta','Оформить подписку');

$form[3][] = lang_form('input td12','lk_subscriptions|page_title','Подписки — Совушкина школа');
$form[3][] = lang_form('input td12','lk_subscriptions|breadcrumbs','Главная / Кабинет / Подписки');
$form[3][] = lang_form('input td12','lk_subscriptions|status','Выбор подписок и тарифа');
$form[3][] = lang_form('input td12','lk_subscriptions|heading','Подписки');
$form[3][] = lang_form('textarea td12','lk_subscriptions|hint','Выберите нужные подписки и тариф — итоговая сумма и скидка пересчитаются автоматически.');
$form[3][] = lang_form('input td6','lk_subscriptions|step1','Выберите подписки');
$form[3][] = lang_form('input td6','lk_subscriptions|step2','Выберите тариф');
$form[3][] = lang_form('input td6','lk_subscriptions|step3','Выгодные предложения');
$form[3][] = lang_form('input td6','lk_subscriptions|view','Посмотреть');
$form[3][] = lang_form('textarea td12','lk_subscriptions|step1_note','Нажмите «Посмотреть», чтобы открыть страницу направления (заглушки для будущих разделов).');
$form[3][] = lang_form('textarea td12','lk_subscriptions|step2_note','Цены указаны за 1 подписку. Итог зависит от количества выбранных подписок.');
$form[3][] = lang_form('textarea td12','lk_subscriptions|step3_note','Скидка применяется автоматически и отображается в расчёте ниже.');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_2','2 подписки — −10%');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_3','3+ подписки — −15%');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_all','Все подписки — −20%');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_none','1 подписка — выгодных предложений нет');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_hint_10','Активирована скидка 10% за 2 подписки');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_hint_15','Активирована скидка 15% за 3+ подписки');
$form[3][] = lang_form('input td6','lk_subscriptions|discount_hint_20','Активирована скидка 20% за все подписки');
$form[3][] = lang_form('input td6','lk_subscriptions|summary_count','Выбрано подписок:');
$form[3][] = lang_form('input td6','lk_subscriptions|summary_tariff','Тариф:');
$form[3][] = lang_form('input td6','lk_subscriptions|summary_subtotal','Стоимость:');
$form[3][] = lang_form('input td6','lk_subscriptions|summary_discount','Скидка:');
$form[3][] = lang_form('input td6','lk_subscriptions|summary_total','Итого:');
$form[3][] = lang_form('input td6','lk_subscriptions|pay','Оформить подписку');
$form[3][] = lang_form('textarea td12','lk_subscriptions|pay_note','Демо: оплата будет подключена позже (через бэкенд / платёжного провайдера).');

$form[4][] = lang_form('input td12','lk_ideas|page_title','Кладовая идей — Совушкина школа');
$form[4][] = lang_form('input td12','lk_ideas|breadcrumbs','Главная / Кабинет / Кладовая идей');
$form[4][] = lang_form('input td12','lk_ideas|status','Подписок нет: материалы здесь доступны бесплатно');
$form[4][] = lang_form('input td12','lk_ideas|heading','Кладовая идей');
$form[4][] = lang_form('textarea td12','lk_ideas|hint','Здесь собраны бесплатные материалы. Нажмите «Посмотреть», чтобы открыть крупный предпросмотр в новом окне, «Скачать», чтобы открыть файл для скачивания в новой вкладке (эта страница останется открытой), а «Описание» — чтобы прочитать подробности.');
$form[4][] = lang_form('input td6','lk_ideas|search_label','Поиск по материалам');
$form[4][] = lang_form('input td6','lk_ideas|search_placeholder','Введите ключевое слово (например: Новый год, кубик, разговоры...)');
$form[4][] = lang_form('input td6','lk_ideas|search_empty','Ничего не найдено. Попробуйте другое слово.');
$form[4][] = lang_form('input td6','lk_ideas|badge_free','Бесплатно');
$form[4][] = lang_form('input td6','lk_ideas|view','Посмотреть');
$form[4][] = lang_form('input td6','lk_ideas|download_pdf','Скачать PDF');
$form[4][] = lang_form('input td6','lk_ideas|download_zip','Скачать ZIP');
$form[4][] = lang_form('input td6','lk_ideas|description','Описание');
$form[4][] = lang_form('input td6','lk_ideas|empty','Пока нет доступных материалов.');
$form[4][] = lang_form('input td6','lk_ideas|modal_cancel','Остаться');
$form[4][] = lang_form('input td6','lk_ideas|modal_confirm','Выйти');


function lang_form($type,$key,$name) {
	global $lang;
	$key = explode('|',$key);
	//автозаполнение пустых полей
	if (/*@$_GET['fuel'] AND */!isset($lang[$key[0]][$key[1]])) $lang[$key[0]][$key[1]] = $name;
	return array ($type,'dictionary['.$key[0].']['.$key[1].']',array(
		'name'=>$name.' {'.$key[0].'|'.$key[1].'}',
		'title'=>$key[0].'|'.$key[1],
		//v1.4.20 - значение в поле
		'value'=>$lang[$key[0]][$key[1]],
		'no_escape'=>true
	));
}