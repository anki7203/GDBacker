$(window).on('load',function() {

	function removeHash(){ history.pushState("", document.title, window.location.pathname+window.location.search); } removeHash();
	if($('body').hasClass('user')){ $('.userbar').removeClass('off'); }

	var randImg = Math.floor(Math.random()*5)+1;
	$('header').css('background-image', 'url("img/header-'+randImg+'.png")');

	$('.login-btn').on('click',function(e){ $('.login-modal').removeClass('hidden'); });
	$('.login-close').on('click',function(e){ $('.login-modal').addClass('hidden'); });

	$('.ach-btn').on('click',function(e){ $('.ach-modal').removeClass('hidden'); $('body').addClass('ach');});
	$('.ach-close').on('click',function(e){ $('.ach-modal').addClass('hidden'); $('body').removeClass('ach');});

	$('.lead-btn').on('click',function(e){ $('.lead-modal').removeClass('hidden'); $('body').addClass('ach');});
	$('.lead-close').on('click',function(e){ $('.lead-modal').addClass('hidden'); $('body').removeClass('ach');});

	$('.logout').on('click',function(){ console.log('logout'); window.location.href = "/gdbacker/app/logout.php?logoutuser=1"; });

	$('.go-home').on('click',function(){ console.log('logout'); window.location.href = "/gdbacker/app/logout.php?logoutuser=1"; });

	$('.logo').on('click',function(){ window.location.href = "/gdbacker/"; });

	$('.login-modal button').on('click',function(){
		var provider = $(this).data('provider');
		window.location.href = "/gdbacker/app/login.php?provider="+provider;
	});

	setUpPosts();
	setPromoLinks(false);
	setTimeout(function(){ checkNotifications(); }, 4000);

});


function closeProject(){
	$('.promo-list').show();
	$('html').removeClass('no-scroll');
	$('#makepost, #sharebtn, .info .success').remove();
	$('.project-modal.active').addClass('hidden').removeClass('active fb rd tw');
	clearTimeout(charCountTimer);
	setTimeout(function(){ 
		var currproject = Cookies.get('current-project');
		$('.project[data-id="'+currproject+'"]').addClass('pulse');
		setTimeout(function(){ 
			$('.project.pulse').removeClass('pulse');
			Cookies.remove('current-project');
		}, 1800); 
	}, 500);
}


function setUpPosts(){

	$('.project').on('click', 'button, img', function(e){
		var projectid = $(this).parents('.project').data('id');
		Cookies.set('current-project', projectid);
		$('html').addClass('no-scroll');
		$(this).siblings('.project-modal').removeClass('hidden').addClass('active');
	});

	$('.upvote').on('click',function(e){ vote($(this),'up'); });
	$('.dnvote').on('click',function(e){ vote($(this),'down'); });

	$('.project-close').on('click',function(e){ closeProject(); e.stopPropagation(); });

	$('.project-loader').remove();

	checkForOpenProject();
}


function vote(ele,vote){
	var projectid = ele.parent().data('id');
	var nuteralize = false;
	var other = false;
	if(ele.hasClass('on')){ nuteralize = true; }
	if(ele.siblings().hasClass('on')){ other = true; }
	var scoreEle = ele.siblings('.score');
	var score = parseInt(scoreEle.text());

	console.log(vote+' - '+nuteralize+' - '+other);

	if(vote == 'up' && nuteralize){ score = score - 1; }
	if(vote == 'up' && !nuteralize){ score = score + 1; }
	if(vote == 'down' && nuteralize){ score = score + 1; }
	if(vote == 'down' && !nuteralize){ score = score - 1; }

	if(vote == 'up' && other){ score = score + 1; }
	if(vote == 'down' && other){ score = score - 1; }

	scoreEle.text(score);
	ele.removeClass('on').siblings().removeClass('on');
	if(!nuteralize){ ele.addClass('on'); }else{ vote = 'nuteralize';}
	$.ajax({ 
		url: "http://andrewkiproff.com/gdbacker/app/vote.php?projectid="+projectid+"&vote="+vote, 
		success: function(result){ 
			updateUserPoints();
		}
	});
}


function setPromoLinks(ele){

	if(ele){ 
		ele = $('.promo-list li.off'); 
		console.log('one promo link set');
	}else{
		ele = $('.promo-list li'); 
		console.log('all promo links set');
	}

	ele.on('click',function(){
		console.log('promo link clicked');
		//$(this).addClass('off');
		//$(this).unbind('click');
		var share = $(this).data('share');
		var projectid = $(this).closest('.info').data('id');
		$.ajax({ 
			url: "http://andrewkiproff.com/gdbacker/app/share.php?projectid="+projectid+"&share="+share, 
			success: function(result){
				if(result.indexOf("redirect") >= 0){
					var data = result.split("||");
					showRedirectMessage(data[1],'It looks like your not linked to '+data[2]+'. Let\'s fix that!');
					//setPromoLinks(true);
					return;
				}
				$('.project-modal.active .info').append(result);
				$('.promo-list').hide();
				$("#makepost .focus").focus();
				var val = $("#makepost .focus").val();
				$("#makepost .focus").val('').val(val);
				$('#makepost textarea').bind('input propertychange',function(){ $('#makepost .marker').fadeOut(); });
				if($('.char-counter').length){ startCharCounter(); }
				setPromoListBG();
				setPromoListBtn();
			},
			error: function(result){
				console.log(result);
			}
		});
	});
}


function setPromoListBG(){ 
	var bg = $('#makepost').data('bg'); $('.project-modal.active').addClass(bg); 
}


function setPromoListBtn(){ 
	$('#sharebtn').on('click',function(){
		$(this).addClass('off');
		$('#makepost').addClass('off');
		$('.project-modal.active .info').append('<i class="fa fa-cog fa-spin fa-3x fa-fw send-loader"></i>');
		var share = $(this).data('share');
		var projectid = $(this).closest('.info').data('id');
		var data = $('#makepost textarea').val();
		data = encodeURIComponent(data);
		sendShareData(projectid,data,share);
	});
}

function sendShareData(projectid,data,share){
	$.ajax({ 
		url: "http://andrewkiproff.com/gdbacker/app/share.php?projectid="+projectid+"&data="+data+"&share="+share, 
		success: function(result){
			if(result.indexOf("true") >= 0){
				var data = result.split(",");
				$('.project-modal.active .info').append('<div class="success"><h1>Congrats!</h1><p>You just made a difference.</p><div class="points"><i class="fa fa-plus" aria-hidden="true"></i> <span>'+data[1]+'</span> points!</div></div>');
				$('#makepost').remove();
				$('.send-loader').remove();
				updateUserPoints();
				$.playSound('js/pts');
				setTimeout(function(){ $('.success .points span').addClass('off'); }, 3000);
				setTimeout(function(){ closeProject(); }, 4500);
			}else if(result.indexOf("redirect") >= 0){
				var data = result.split("||");
				showRedirectMessage(data[1],'It looks like your not linked to '+data[2]+'. Let\'s fix that!');
			}else{
				alert(result);
				window.location.replace("http://andrewkiproff.com/gdbacker/");
			}
		}
	});
}

function showRedirectMessage(url,msg){
	$('.message').removeClass('hidden');
	$('.message p').text(msg);
	$('.message button').on('click',function(){ $(this).unbind('click'); window.location.replace(url); });
	$('.message span').on('click',function(){ $('.message').addClass('hidden'); });
}

function updateUserPoints(){
	$('.user-info span').text(Cookies.get('points'));
}


function checkUserVote(){
	$(".vote-box").each(function(i){
		var projectid = $(this).data('id');
		$.ajax({ 
			url: "http://andrewkiproff.com/gdbacker/app/vote.php?projectid="+projectid+"&check=1", 
			success: function(result){ 
				if(result == 'up'){ $('*[data-id="'+projectid+'"] .upvote').addClass('on'); }
				if(result == 'down'){ $('*[data-id="'+projectid+'"] .dnvote').addClass('on'); }
			}
		});
	});
}


var charCountTimer;
function startCharCounter(){
	var ele = $('.count-chars');
	var input = ele.val();
	var count = 0;
	if(typeof input !== 'undefined') { count = input.length; }
	var tot = 0; 

	if(ele.parents('tweet').length != -1){ tot = 140; }
	if(ele.parents('reddit').length != -1){ tot = 300; }

	count = tot-count;

	$('.char-counter').text(count);

	$('.char-counter').removeClass('warn superwarn');
	if(count < 21){ $('.char-counter').addClass('warn'); }
	if(count < 11){ $('.char-counter').addClass('superwarn'); }

	if(count < 0){ 
		$('#sharebtn').attr('disabled','disabled').addClass('disabled'); 
	}else{ 
		$('#sharebtn').removeAttr('disabled').removeClass('disabled'); 
	}

	charCountTimer = setTimeout(function(){ startCharCounter(); }, 200);
}


function checkForOpenProject(){
	if(Cookies.get('projectid')){
		var projectid = Cookies.get('projectid');
		var projectshare = Cookies.get('projectshare');
		var ele = $('.project[data-id="'+projectid+'"]');

		$('html,body').animate({scrollTop: ele.offset().top - 50 });

		ele.children('.project-modal').removeClass('hidden').addClass('active');

			$.ajax({ 
				url: "http://andrewkiproff.com/gdbacker/app/share.php?projectid="+projectid+"&share="+projectshare, 
				success: function(result){ 
					$('.project-modal.active .info').append(result);
					$('.promo-list').hide();
					$("#makepost .focus").focus();
					var val = $("#makepost .focus").val();
					$("#makepost .focus").val('').val(val);
					$('#makepost textarea').bind('input propertychange',function(){ $('#makepost .marker').fadeOut(); });
					if($('.char-counter').length){ startCharCounter(); }
					setPromoListBG();
					setPromoListBtn();
					Cookies.remove('projectid');
					Cookies.remove('projectshare');
				}
			});
		}
}


function checkNotifications(){
	if($('.notifications li').length){
		$.playSound('js/ach');
		$('.notifications').removeClass('hidden');
		$('.notifications li:first').addClass('active');
		$('.notifications li button').on('click',function(){
			var nick = $(this).data('nick');
			var userid = Cookies.get('userid');
			$.ajax({ 
				url: "http://andrewkiproff.com/gdbacker/app/achievements.php?clearNotification=1&nick="+nick+"&userid="+userid,
				success: function(result){
					if($('.notifications li').length > 1){
						$('.notifications li.active').remove();
						setTimeout(function(){ $('.notifications li:first').addClass('active'); $.playSound('js/ach'); }, 1000);
					}else{
						$('.notifications li.active').remove();
						$('.notifications').addClass('hidden');
					}
					updateUserPoints();
				}
			});
		});
	}
}