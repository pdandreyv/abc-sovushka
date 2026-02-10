;(function($) {
	$.fn.ajaxSubmit = function(options) {
		var form = this,
			formData = new FormData($(form).get(0)),
			type = 'html',
			iframe = false;
		if (options.dataType) type = options.dataType;
		if (options.iframe) iframe = options.iframe;
		if (iframe) type = 'html';
		//console.log(options);
		//console.log(type+' '+iframe);
		$.ajax({
			url: options.url,
			type: 'POST',
			dataType: type,
			contentType: false,
			processData: false,
			data: formData,
			cache: false,
			beforeSend: function(){
				if (options.beforeSubmit) {
					options.beforeSubmit();
				}
			},
			success: function(response, textStatus){
				var data = response;
				if (iframe) {
					// извлекаем содержимое <textarea>...</textarea> (ответ может содержать PHP Warning до тега)
					var startTag = '<textarea>';
					var endTag = '</textarea>';
					var start = (typeof data === 'string' && data.indexOf(startTag) >= 0)
						? data.indexOf(startTag) + startTag.length
						: 0;
					var end = (typeof data === 'string' && data.indexOf(endTag, start) >= 0)
						? data.indexOf(endTag, start)
						: data.length;
					data = typeof data === 'string' ? data.substring(start, end).trim() : '';
					try {
						if (!data || (data.charAt(0) !== '{' && data.charAt(0) !== '[')) {
							throw new Error('Ответ сервера не JSON');
						}
						data = JSON.parse(data);
					} catch (e) {
						if (options.error) {
							options.error();
						} else if (options.success) {
							options.success({ error: 'Ошибка ответа сервера. Проверьте логи (PHP Notice/Warning).' });
						}
						return;
					}
				}
				if (options.success) {
					options.success(data);
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown){
				if (options.error) {
					options.error();
				}
			},
			complete: function(XMLHttpRequest, textStatus){
			}
		});
	}
})(jQuery);