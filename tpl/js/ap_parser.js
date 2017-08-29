(function ($) {

	// Set Regular Expression of URL (the Source from XE Auto Link Add-on)
	var protocol_re = '(?:(?:https?)://)';
	var domain_re = '(?:[^\\s./)>]+\\.)+[^\\s./)>]+';
	var max_255_re = '(?:1[0-9]{2}|2[0-4][0-9]|25[0-5]|[1-9]?[0-9])';
	var ip_re = '(?:' + max_255_re + '\\.){3}' + max_255_re;
	var port_re = '(?::([0-9]+))?';
	var user_re = '(?:/~\\w+)?';
	var path_re = '(?:/[^\\s]*)?';
	var hash_re = '(?:#[^\\s]*)?';
	var url_regex = new RegExp('(' + protocol_re + '(' + domain_re + '|' + ip_re + '|localhost' + ')' + port_re + user_re + path_re + hash_re + ')', 'ig');

	// Select a Dom in Which You Would Take Links
	if (ap_parser_target == 'doc') x = $('.xe_content[class^=document_]');
	else if (ap_parser_target == 'cmt') x = $('.xe_content[class^=comment_]');
	else x = $('.xe_content');

	// Extract Paragraphs with Regulr Expression
	var ps = x.find('p').filter(function () {
		return $(this).text().match(url_regex);
	});
	if (ps.length < 1) return;

	// Make URL Array without Exceptive Domain
	var urls = [];
	if (ap_parser_exception) ap_parser_exception = ap_parser_exception.split(',');
	ps.each(function (i) {
		var matches = $(this).text().match(url_regex);
		for (n = 0; n < matches.length; n++) {
			var _exp = true;
			if (ap_parser_exception.length > 0) {
				$.each(ap_parser_exception, function (idx, val) {
					if (matches[n].indexOf(val) != -1) _exp = false;
				});
			}
			if (_exp == true) {
				urls.push(matches[n]);
				// Insert Preview Container
				$(this).after(ap_parser_output);
			}
		}
	});

	// Set Element Names to Get Each Indicator For Skin Developers
	var container = 'ap_parser',
		load = 'ap_parser_loading',
		cnt = 'ap_parser_content',
		wrp = 'ap_parser_image_wrap',
		imgs = 'ap_parser_images',
		nav = 'ap_parser_total_image_nav',
		num = 'ap_parser_cur_image_num',
		tot = 'ap_parser_total_images',
		tit = 'ap_parser_title',
		uri = 'ap_parser_url',
		desc = 'ap_parser_desc';

	// Prepend ID prefix For the Rhymix System
	var prefix = 'user_content_';

	// Set ID Attribute
	$('.' + container).css('text-align', ap_parser_printing_align).each(function (i) {
		var apo = $(this);
		apo.find('.' + load).attr('id', prefix + load + i);
		apo.find('.' + cnt).attr('id', prefix + cnt + i);
		apo.find('.' + wrp).attr('id', prefix + wrp + i);
		apo.find('.' + imgs).attr('id', prefix + imgs + i);
		apo.find('.' + nav).attr('id', prefix + nav + i);
		apo.find('.' + nav + ' a:first').attr('id', prefix + 'prev' + i);
		apo.find('.' + nav + ' a:last').attr('id', prefix + 'next' + i);
		apo.find('.' + num).attr('id', prefix + num + i);
		apo.find('.' + tot).attr('id', prefix + tot + i);
		apo.find('.' + tit).attr('id', prefix + tit + i);
		apo.find('.' + uri).attr('id', prefix + uri + i);
		apo.find('.' + desc).attr('id', prefix + desc + i);
		if (ap_parser_loading_image) $('#' + prefix + load + i).hide();
		// Load the Containers in Viewport ( http://jsfiddle.net/wilsonjonash/c7nS5/27/ )
		var _alreadyVisible = false;
		$(window).on('load scroll resize', function () {
			if (isTargetVisble(apo)) {
				if (_alreadyVisible == false) {
					_alreadyVisible = true;
					parseLink(i);
				}
			}
		});
	});

	$.fn.is_on_screen = function () {
		var win = $(window);
		var viewport = {
			top: win.scrollTop(),
			left: win.scrollLeft()
		};
		viewport.right = viewport.left + win.width();
		viewport.bottom = viewport.top + win.height();

		var bounds = this.offset();
		bounds.right = bounds.left + this.outerWidth();
		bounds.bottom = bounds.top + this.outerHeight();

		return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
	};

	function isTargetVisble(el) {
		var retunVal = false;
		el.each(function () {
			if ($(this).is_on_screen()) retunVal = true;
		});
		return retunVal;
	}

	function parseLink(i) {
		if (!isValidURL(urls[i])) {
			return false;
		} else {
			if (!ap_parser_facebook_embed && urls[i].indexOf('facebook.com') != -1) getFacebook(i);
			else if (!ap_parser_twitter_embed && urls[i].indexOf('twitter.com') != -1) getTwitter(i);
			else if (!ap_parser_instagram_embed && (urls[i].indexOf('instagram.com') != -1 || urls[i].indexOf('instagr.am') != -1)) getInstagram(i);
			else if (!ap_parser_youtube_embed && (urls[i].indexOf('youtube.com/') != -1 || urls[i].indexOf('youtu.be/') != -1)) getYoutube(i);
			else getPreview(i);
		}
	}

	function isValidURL(url) {
		var RegExp = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
		if (RegExp.test(url)) return true;
		else return false;
	}

	function getFacebook(i) {
		var fb_cnt = $('#' + prefix + cnt + i),
			fb_load = $('#' + prefix + load + i);
		if (urls[i].indexOf('/posts/') != -1) {
			// Facebook Post
			$.ajax({
				url: 'https://www.facebook.com/plugins/post/oembed.json/?url=' + urls[i],
				dataType: 'jsonp',
				success: function (data) {
					// Flip Viewable Content
					fb_cnt.css({'width': 'auto', 'border': 'none'}).html(data.html).fadeIn('slow');
					fb_load.hide();
					if (ap_parser_link_text) {
						var p = fb_cnt.parent('.' + container).prev('p');
						if (p.text().indexOf(urls[i]) != -1) {
							if (p.text() == urls[i]) p.remove();
							else p.html(p.text().replace(urls[i], ''));
						}
					}
				},
				error: function () {
					getPreview(i);
				}
			});
		} else {
			// Facebook Page
			var regExp = /(?:(?:http|https):\/\/)?(?:www.)?facebook.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[?\w\-]*\/)?([\w\-]*)?/;
			var matches = urls[i].match(regExp);
			if (urls[i].indexOf('/groups/') != -1 || urls[i].indexOf('profile.php') != -1) getPreview(i);
			else if (matches[1]) {
				var fb_page = '<iframe src="https://www.facebook.com/plugins/page.php?href=' + urls[i];
				fb_page += '&tabs&width=500&height=130&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=false"';
				fb_page += ' width=500 height=130" style="width:500px;border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe>';
				fb_cnt.html(fb_page).css({'border': 'none'}).fadeIn('slow');
				fb_load.hide();
				if (ap_parser_link_text) {
					var p = fb_cnt.parent('.' + container).prev('p');
					if (p.text().indexOf(urls[i]) != -1) {
						if (p.text() == urls[i]) p.remove();
						else p.html(p.text().replace(urls[i], ''));
					}
				}
			}
			else getPreview(i);
		}
	}

	function getTwitter(i) {
		var regExp = /http(?:s)?:\/\/(?:www\.)?twitter\.com\/([a-zA-Z0-9_]+)/;
		var matches = urls[i].match(regExp);
		if (matches && $.inArray(matches[1], ['i', 'search', 'login', 'signup']) == -1) {
			var url_match = urls[i].replace('//', '/').split('/');
			var tw_cnt = $('#' + prefix + cnt + i),
				tw_load = $('#' + prefix + load + i);
			if (url_match[3] == 'status') {
				// Twitter Post
				$.ajax({
					url: 'https://publish.twitter.com/oembed',
					data: {hide_thread: true, url: urls[i]},
					dataType: 'jsonp',
					success: function (data) {
						tw_cnt.css({'border': 'none'}).html(data.html).fadeIn('slow');
						tw_load.hide();
						if (ap_parser_link_text) {
							var p = tw_cnt.parent('.' + container).prev('p');
							if (p.text().indexOf(urls[i]) != -1) {
								if (p.text() == urls[i]) p.remove();
								else p.html(p.text().replace(urls[i], ''));
							}
						}
					},
					error: function () {
						getPreview(i);
					}
				});
			} else {
				// Twitter Timeline
				var tw_post = '<a class="twitter-timeline" data-dnt="true" data-tweet-limit="1" href="' + urls[i] + '">Tweets by ' + url_match[2] + '</a>';
				tw_post += '<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';
				tw_cnt.html(tw_post).css({'max-width': 500, 'border-radius': 4, 'overflow-y': 'auto'}).fadeIn('slow');
				tw_load.hide();
				if (ap_parser_link_text) {
					var p = tw_cnt.parent('.' + container).prev('p');
					if (p.text().indexOf(urls[i]) != -1) {
						if (p.text() == urls[i]) p.remove();
						else p.html(p.text().replace(urls[i], ''));
					}
				}
				tw_cnt.css('max-height', tw_cnt.width());
				$(window).on('resize', function () {
					tw_cnt.css('max-height', tw_cnt.width());
				});
			}
		} else getPreview(i);
	}

	function getInstagram(i) {
		var url_match = urls[i].replace('//', '/').split('/');
		if (url_match[2] == 'p') {
			// Instagram Post
			var ig_cnt = $('#' + prefix + cnt + i),
				ig_load = $('#' + prefix + load + i);
			$.ajax({
				url: 'https://api.instagram.com/oembed',
				data: {url: urls[i].replace(url_match[4], '')},
				dataType: 'jsonp',
				success: function (data) {
					ig_cnt.css({'border': 'none'}).html(data.html).fadeIn('slow');
					ig_load.hide();
					if (ap_parser_link_text) {
						var p = ig_cnt.parent('.' + container).prev('p');
						if (p.text().indexOf(urls[i]) != -1) {
							if (p.text() == urls[i]) p.remove();
							else p.html(p.text().replace(urls[i], ''));
						}
					}
				},
				error: function () {
					getPreview(i);
				}
			});
		} else {
			// Instagram Profile
			getPreview(i);
			var regExp = /http(?:s)?:\/\/(?:www\.)?instagram\.com\/([a-zA-Z0-9_]+)/;
			var matches = urls[i].match(regExp);
			if (matches && $.inArray(matches[1], ['', 'about', 'developer', 'legal', 'explore']) == -1) {
				$.exec_json('parserlink.getInstagram', {'username': url_match[2]}, function (data) {
					var src = '';
					$.each(data.data, function (index, value) {
						src += '<a class="ap_parser_insta_link" href="https://www.instagram.com/p/' + value.code + '">'
						src += '<img class="ap_parser_insta_thumb" src="' + value.thumbnail_src + '" style="width: 24%; margin: 0 .5%;" />';
						src += '</a>';
					});
					$('#' + prefix + cnt + i + ' .ap_parser_image_wrap, #' + prefix + cnt + i + ' .ap_parser_info').wrapAll('<div />');
					$('#' + prefix + cnt + i).append('<div class="ap_parser_insta" style="padding: 10px 20px 20px" />').css('border-radius', 4);
					$('#' + prefix + cnt + i).children('.ap_parser_insta').html(src);
				});
			}
		}
	}

	function getYoutube(i) {
		var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/;
		var matches = urls[i].match(regExp);
		if (matches) {
			var yt_id = matches[7];
			var yt_list = urls[i].match('(list=[a-zA-Z0-9\-\_]+&?)');
			yt_list = yt_list ? '?' + yt_list[0] : '';
			var yt_cnt = $('#' + prefix + cnt + i),
				yt_load = $('#' + prefix + load + i);
			var _yt_frame = '<img id="' + yt_id + '" src="https://i.ytimg.com/vi/' + yt_id + '/mqdefault.jpg" style="display: none;">';
			_yt_frame += '<iframe allowfullscreen="" frameborder="0" src="https://www.youtube.com/embed/' + yt_id + yt_list + '"></iframe>';
			yt_cnt.html(_yt_frame);
			var yt_frame = yt_cnt.children('iframe');
			yt_frame.css({'width': '100%'});
			$(yt_cnt.children('img#' + yt_id)).on('load', function () {
				var ratio = this.height / this.width;
				yt_frame.css({'height': yt_frame.width() * ratio});
				$(window).on('resize', function () {
					yt_frame.css('height', yt_frame.width() * ratio);
				});
			});
			yt_cnt.css({'max-width': ap_parser_youtube_max, 'border': 'none'}).fadeIn('slow');
			yt_load.hide();
			if (ap_parser_link_text) {
				var p = yt_cnt.parent('.' + container).prev('p');
				if (p.text().indexOf(urls[i]) != -1) {
					if (p.text() == urls[i]) p.remove();
					else p.html(p.text().replace(urls[i], ''));
				}
			}
		} else getPreview(i);
	}

	function getPreview(i) {
		$.ajax({
			url: './addons/ap_parser/ap_parser.php',
			data: {url: urls[i], img_len: ap_parser_image_length},
			dataType: 'json',
			method: 'POST',
			success: function (data) {
				if (data == null || data.title == null || data.title == '') $('#' + prefix + cnt + i).parent('.' + container).remove();
				else {
					// Hide .wsfr and Show Loading Image
					$('.wfsr').hide();
					if (ap_parser_link_text) {
						var p = $('#' + prefix + cnt + i).parent('.' + container).prev('p');
						if (p.text().indexOf(urls[i]) != -1) {
							if (p.text() == urls[i]) p.remove();
							else p.html(p.text().replace(urls[i], ''));
						}
					}

					// Set Content of Title
					$('#' + prefix + load + i).append('<input type="hidden" name="' + tit + i + '" value="' + data.title + '" />');
					var parsed_title = ap_parser_title_length ? $('input[name=' + tit + i + ']').val().substr(0, ap_parser_title_length) : data.title;
					$('#' + prefix + tit + i + ' a').attr('href', urls[i]).html(parsed_title);
					$('input[name=' + tit + i + ']').remove();

					// Set Content of URL, Current Image Information, and the Number of Total Images
					var domain = urls[i].split('//')[1].split('/')[0];
					ap_parser_print_domain ? $('#' + prefix + uri + i).remove() : $('#' + prefix + uri + i + ' a').attr('href', urls[i]).html(domain);
					var total_images = parseInt(data.total_images);

					// Set Content of Description
					if (data.description) {
						$('#' + prefix + load + i).append('<input type="hidden" name="' + desc + i + '" value="' + data.description + '" />');
						var parsed_content = ap_parser_content_length ? $('input[name=' + desc + i + ']').val().substr(0, ap_parser_content_length) : data.description;
						$('#' + prefix + desc + i).html(parsed_content);
						$('input[name=' + desc + i + ']').remove();
					} else $('#' + prefix + desc + i).remove();

					// Set Image Container
					if (total_images == 0) {
						$('#' + prefix + wrp + i).remove();
					} else if (total_images > 0) {
						// Set Element Names for Using Script
						var img_id = 'ap_parser_img';
						$('#' + prefix + imgs + i).parent('a').attr('href', urls[i]);
						if (total_images == 1) {
							$('#' + prefix + nav + i).remove();
							var img_src = '';
							img_src = (data.images[0].img.indexOf('http') != -1) ? data.images[0].base64 : '//' + domain + data.images[0].base64;
							$('#' + prefix + imgs + i).append('<img src="' + img_src + '" id="' + prefix + img_id + i + '_1">');
						} else if (total_images > 1) {
							$('#' + prefix + tot + i).html(total_images);
							$('#' + prefix + imgs + i).html('');
							$.each(data.images, function (a, b) {
								var img_src = '';
								img_src = (b.img.indexOf('http') != -1) ? b.base64 : '//' + domain + b.base64;
								$('#' + prefix + imgs + i).append('<img src="' + img_src + '" id="' + prefix + img_id + i + '_' + (a + 1) + '">');
							});
							$('#' + prefix + imgs + i + ' img').hide();
						}
						$('#' + prefix + imgs + i + ' img').on('error', function () {
							$('#' + prefix + wrp + i).remove();
						});
					}

					// Flip Viewable Content
					$('#' + prefix + cnt + i).fadeIn('slow');
					$('#' + prefix + load + i).hide();

					// Show first image
					if (total_images > 0) {
						$('img#' + prefix + img_id + i + '_1').fadeIn();
						if (total_images > 1) {
							$('#' + prefix + num + i).html(1);

							// next image
							$('#' + prefix + 'next' + i).on('click', function () {
								var index = $('#' + prefix + num + i).html();
								$('img#' + prefix + img_id + i + '_' + index).hide();
								if (index < total_images) {
									new_index = parseInt(index) + parseInt(1);
								} else {
									new_index = 1;
								}
								$('#' + prefix + num + i).html(new_index);
								$('img#' + prefix + img_id + i + '_' + new_index).show();
								return false;
							});

							// prev image
							$('#' + prefix + 'prev' + i).on('click', function () {
								var index = $('#' + prefix + num + i).html();
								$('img#' + prefix + img_id + i + '_' + index).hide();
								if (index > 1) {
									new_index = parseInt(index) - parseInt(1);
									;
								} else {
									new_index = total_images;
								}
								$('#' + prefix + num + i).html(new_index);
								$('img#' + prefix + img_id + i + '_' + new_index).show();
								return false;
							});
						}
					}
				}
			},
			error: function () {
				$('#' + prefix + cnt + i).parent('.' + container).remove();
			}
		});
	}

	$('.' + cnt + ' a').on('click', function () {
		var href = $(this).attr('href');
		var host = $(location).attr('hostname');
		href.indexOf(host) != -1 ? $(this).attr('target', ap_parser_internal_link) : $(this).attr('target', ap_parser_external_link);
	});

})(jQuery);