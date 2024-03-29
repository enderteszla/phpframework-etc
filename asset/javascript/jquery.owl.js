/** jQuery own written library **/

$.owl = {
	id: function (str,prefix) {
		return str.replace(RegExp('^.*' + (prefix ? prefix.replace(/(\\?)[\-]/, function ($0, $1) {
			return $1 ? $0 : '\\' + $0;
		}) : 'id') + '\\-(\\d+).*$'), '$1');
	},
	url: function (str) {
		return str.replace(/^url\('?(.*)'?\)$/, "$1");
	}
};
/**
 * Почему? Зачем этот файл?
 * Ты знаешь, что такое NIH (Not-Invented-Here)?
 * Согласно википедии,
 *  "Not invented here (NIH) is the philosophical principle of not using third party solutions
 *  to a problem because of their external origins. It is usually employed in favor of employer's
 *  own solution to a given problem, though not necessarily so; NIH's emphasis is on ignoring,
 *  boycotting, or otherwise refusing to acknowledge others' solutions."
 * Думаю, твоего английского должно хватить.
 *
 * В общем, я всегда плевался на этот принцип, используемый компанией Microsoft.
 * Но после начала работы с тобой изменил своё мнение на этот счёт.
 *
 * Ты используешь js-плагины бездумно. В этом есть плюсы и минусы:
 *  + твоя front-end разработка обладает огромной скоростью;
 *  - ты подключаешь не пойми что, не пойми как, не пойми в каком порядке.
 *
 * Надо избавляться от мусора.
 * Поэтому две настоятельных просьба:
 *  1. Писать комментарии, когда подключаешь какое-либо стороннее решение.
 *  2. При поиске решений какой-либо задачи отдавать предпочтение уже написанным нами решениям
 *      (что, разумеется, подразумевает, что я буду писать плагины для замены уже использованных
 *      тобой; при твоём желании могу подключить тебя в этот процесс: показать, как я это делаю,
 *      и помочь тебе научиться делать то же.
 * */

/**
 * Что делает эта функция?
 *  (Циклически) сдвигает содержимое каждого контейнера на один элемент влево/вправо (при условии, что их больше одного).
 * Как использовать?
 *  jQueryElements.move({
 *      direction: 'left'/'right', направление сдвига, обязательно
 *      rate: 5, скорость (в секундах), необязательно
 *      specCss: {}, CSS-свойства, которые будут повешены на активный элемент, необязательно
 *      specClass: '', класс, который будет присвоен активному элементу, необязательно
 *      specFunction: function(current,all) функция, которая применяется к активному и остальным элементам, необязательно
 *  });
 *  где jQueryElements - jQuery-коллекция из одного или нескольких контейнеров
 * */
$.fn.move = function(settings){
	return this.each(function(){
		var element = $(this);
		if(element.children().not('.hidden').length < 2){
			return true;
		}
		settings = $.extend({direction: 'left',rate: 5},settings);
		settings.specCss = (settings.specCss) ? settings.specCss : {};
		settings._specCss = {};
		if(typeof settings.specCss == 'object'){
			for(key in settings.specCss){
				settings._specCss[key] = '';
			}
		}
		var ml0 = element.children().not('.hidden').last().css('margin-left');
		var ml = (parseInt(ml0) - element.width()/element.children().not('.hidden').length) + 'px';
		var rate = settings.rate;
		if(settings.direction == 'left'){
			if(parseInt(element.children().not('.hidden').eq(0).css('margin-left')) < parseInt(ml0)){
				element.children().not('.hidden').removeClass('left').removeClass('right').eq(0).css({'margin-left':ml0,'transition':''}).detach().appendTo(element);
			}
			element.children().not('.hidden').eq(0).css({'margin-left':ml,'transition':rate + 's'}).addClass('left');
			var all = element.children().not('.hidden').removeClass(settings.specClass).css(settings._specCss);
			var current = element.children().not('.hidden').eq(1).addClass(settings.specClass).css(settings.specCss);
			if(typeof settings.specFunction == 'function'){
				settings.specFunction(current,all);
			}
		} else {
			if(element.children().not('.hidden').eq(0).hasClass('left')){
				element.children().not('.hidden').removeClass('left').removeClass('right').eq(0).css('margin-left',ml0).addClass('right');
			} else {
				element.children().not('.hidden').removeClass('left').removeClass('right').eq(0).css({'margin-left':ml0,'transition':''});
				element.children().not('.hidden').last().detach().prependTo(element).css('margin-left',ml).addClass('right');
				setTimeout(function(){
					element.children().not('.hidden').eq(0).css({'margin-left':ml0,'transition':rate + 's'});
				},0);
			}
			var all = element.children().not('.hidden').removeClass(settings.specClass).css(settings._specCss);
			var current = element.children().not('.hidden').eq(0).addClass(settings.specClass).css(settings.specCss);
			if(typeof settings.specFunction == 'function'){
				settings.specFunction(current,all);
			}
		}
	});
};

/**
 * Что делает эта функция?
 *  Загружает результат POST-запроса в качестве содержимого элемента.
 * Как использовать?
 *  jQueryElement.loadContent(url,data,success);
 *  где jQueryElement - элемент, в который будет загружен результат POST-запроса
 * */
$.fn.loadContent = function(url,data,success){
	$.ajax({
		cache: false,
		data: data,
		dataType: 'json',
		success: function (response){
			if(response.status == 'OK'){
				this.html(response.data);
				if(typeof(success) == 'function'){
					success();
				}
			}
		}.bind(this),
		type: 'POST',
		url: url
	});
	return this;
};

/**
 * Что делает эта функция?
 *  Реализует функционал попапа; содержимое попапа - результат POST-запроса.
 * Как использовать?
 *  var popup = jQueryElement.popup({
 *      popupClass: '', класс, который будет присвоен элементу #popup, необязательно
 *      open: function(popupElement,overlayElement,popup), функция, выполняемая сразу после открытия попапа, необязательно
 *      close: function(popupElement,overlayElement,popup) функция, выполняемая перед закрытием попапа, необязательно
 *  }).open(url,data);
 *  ...
 *  popup.close();
 *  где jQueryElement - элемент, на который будет повешен класс fixed при открытии попапа
 * */
$.fn.popup = function(settings) {
	settings = $.extend({
		beforeOpen: function(){
			return true;
		},
		beforeClose: function(){
			return true;
		}
	},settings);

	var overlayElement = $('#overlay'),
		popupElement = $('#popup'),
		clear = (function(){
			if(overlayElement.length && popupElement.length){
				return function(){
					overlayElement.removeAttr('class').html('');
					popupElement.removeAttr('class').html('');
				};
			} else {
				overlayElement.remove();
				overlayElement = $('<div id="overlay"></div>').appendTo('body');
				popupElement.remove();
				popupElement = $('<div id="popup"></div>').appendTo('body');
				return function(){
					overlayElement.remove();
					popupElement.remove();
				};
			}
		})();

	var root = this;
	return this.data('popup',{
		open: function(url,data){
			var popup = root.data('popup');
			$.when(settings.beforeOpen(popupElement,overlayElement)).then(function(status){
				if(!status){
					clear();
					return popup;
				}
				if(!data){
					data = {};
				}
				popupElement.loadContent(url,data,function(){
					root.addClass('fixed');
					$(document).keyup(function(e){
						if(e.which == 27){
							popup.close();
						}
					});
					overlayElement.click(function(e){
						popup.close();
					});
					if(typeof settings.afterOpen == 'function'){
						settings.afterOpen(popupElement,overlayElement);
					}
				}).addClass(settings.popupClass);
				return popup;
			});
		},
		close: function(){
			$.when(settings.beforeClose(popupElement,overlayElement)).then(function(status){
				if(!status){
					return this;
				}
				clear();
				root.removeClass('fixed');
				$(document).unbind('keyup');
				var popup = root.data('popup');
				root.removeData('popup');
				if(typeof settings.afterClose == 'function'){
					settings.afterClose(popup);
				}
				return this;
			});
		},
		userData: {}
	});
};

$.fn.seek = function(selector){
	return this.find(selector).andSelf().filter(selector);
};

function lcfirst(str) {
	str += '';
	var f = str.charAt(0).toLowerCase();
	return f + str.substr(1);
}

function delay(duration){
	var t = (new Date()).getTime();
	while((new Date()).getTime() < t + duration);
	return true;
}

$.fn.autoResize = function(settings){
	settings = $.extend({},settings);
	var w, h, css = {};
	this.children().not('.hidden').each(function(){
		switch(settings.width){
			case 'sum':
				if(isNaN(w)){
					w = 0;
				}
				w += $(this).width();
				break;
			case 'max':
				if(isNaN(w)){
					w = 0;
				}
				w = Math.max(w,$(this).width());
				break;
			case 'min':
				if(isNaN(w)){
					w = Infinity;
				}
				w = Math.min(w,$(this).width());
				break;
		}
		switch(settings.height){
			case 'sum':
				if(isNaN(h)){
					h = 0;
				}
				h += $(this).height();
				break;
			case 'max':
				if(isNaN(h)){
					h = 0;
				}
				h = Math.max(h,$(this).height());
				break;
			case 'min':
				if(isNaN(h)){
					h = Infinity;
				}
				h = Math.min(h,$(this).height());
				break;
		}
	});
	if(w){
		css.width = w + 'px';
	}
	if(h){
		css.height = h + 'px';
	}
	return this.css(css);
};

$.fn.firstParent = function(selector){
	return this.add(this.parentsUntil(selector).last()).parent(selector);
};

$.fn.generator = function(settings){
	if(typeof settings == "string"){
		settings = {target: settings};
	}
	settings = $.extend({
		button: ".generator-button",
		content: ".generator-content",
		buttonFile: ".generator-button-file",
		file: "generator-file"
	},settings);
	return this.delegate(settings.button,'click',function(e){
		$($(e.target).find(settings.content).html()).appendTo($(settings.target)).trigger('generated');
	}).delegate(settings.buttonFile,'click',function(e){
		$(e.target).find('.' + settings.file).on('change',function(f){
			var generated = $($(e.target).find(settings.content).html()).appendTo($(settings.target)),
				generatedFile = generated.find('input[type="file"]'),
				file = $(f.target).off('change').attr('name',generatedFile.attr('name')).attr('class',generatedFile.attr('class')).replaceAll(generatedFile);
			$("<input type=\"file\" autocomplete=\"off\" />").addClass(settings.file).prependTo($(e.target));
			generated.trigger('generated');
		}).trigger('click');
	});
};