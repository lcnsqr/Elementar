$(function(){$("body").click(function(){$("body > .tree_listing_menu").fadeOut("fast",function(){$(this).remove()});$(".label > a.current").removeClass("current")});$(".label > a").live("click",function(b){b.preventDefault();$("body > .tree_listing_menu").fadeOut("fast",function(){$(this).remove()});$(".label > a.current").removeClass("current");$(this).addClass("current");var a=$(this).parents(".tree_listing_row").first().find(".tree_listing_menu");var c=$(a).clone();$(c).css({left:b.pageX+"px",top:b.pageY+"px"});$("body").append(c);$(c).fadeIn("fast")});$("a.fold.folder_switch").live("click",function(b){b.preventDefault();var d=$(this).attr("href");var c=$(this).parents(".tree_parent").first().find(".tree_listing").first();var a=$(this);$.post("/backend/content/xhr_render_tree_listing",{id:d},function(e){if(e.done==true){$(c).html(e.html);$(c).slideDown("fast","easeInSine");$(a).addClass("unfold");$(a).removeClass("fold")}},"json")});$("a.unfold.folder_switch").live("click",function(a){a.preventDefault();var b=$(this).parents(".tree_parent").first().find(".tree_listing").first();$(b).slideUp("fast","easeOutSine");$(this).addClass("fold");$(this).removeClass("unfold")});$("a.new.content").live("click",function(a){a.preventDefault();$("#blocker").fadeIn("fast");var b=$(this).attr("href");$.post("/backend/content/xhr_render_content_new",{id:b},function(c){if(c.done==true){$("#content_window").html(c.html).show()}$("#blocker").stop().fadeOut("fast")},"json")});$("a.new.element").live("click",function(a){a.preventDefault();$("#blocker").fadeIn("fast");var b=$(this).attr("href");$.post("/backend/content/xhr_render_element_new",{id:b},function(c){if(c.done==true){$("#content_window").html(c.html).show()}$("#blocker").stop().fadeOut("fast")},"json")});$("a.edit.content,a.edit.template,a.edit.meta").live("click",function(b){b.preventDefault();$("#blocker").fadeIn("fast");var c=$(this).attr("href");if($(this).hasClass("content")){var a="content"}else{if($(this).hasClass("template")){var a="template"}else{if($(this).hasClass("meta")){var a="meta"}}}$.post("/backend/content/xhr_render_content_form",{id:c,editor:a},function(d){if(d.done==true){$("#content_window").html(d.html).show(function(){$("#content_editor_form").find("textarea").wysiwyg()})}$("#blocker").stop().fadeOut("fast")},"json")});$("a.edit.element").live("click",function(a){a.preventDefault();$("#blocker").fadeIn("fast");var b=$(this).attr("href");$.post("/backend/content/xhr_render_element_form",{id:b},function(c){if(c.done==true){$("#content_window").html(c.html).show(function(){$("#content_editor_form").find("textarea").wysiwyg()})}$("#blocker").stop().fadeOut("fast")},"json")});$("a.remove").live("click",function(d){d.preventDefault();if($(this).hasClass("content")){var e="/backend/content/xhr_erase_content"}else{if($(this).hasClass("element")){var e="/backend/content/xhr_erase_element"}}$("#blocker").fadeIn("fast");var f=$(this).attr("href");var b=$("#tree_listing_1").find('p.label > a[href="'+f+'"]').parents(".tree_parent").first();var a=$(b).parents(".tree_listing").first();var c=$(b).parents(".tree_parent").first();if(confirm($(this).attr("title")+"?")){$.post(e,{id:f},function(g){if(g.done==true){showClientWarning(g.message);$(b).slideUp("fast","easeOutSine",function(){$(this).remove();if($(a).children().length==0){$(a).hide();$(c).find(".tree_listing_bullet").first().html('<span class="bullet_placeholder">&nbsp;</span>')}})}$("#blocker").stop().fadeOut("fast")},"json")}else{$("#blocker").stop().fadeOut("fast")}});$(window).mousedown(function(a){mouseButton=1});$(window).mouseup(function(c){if($("#tree_drag_container").children().length>0){var g=$("#tree_drag_container").find("p.label").first();var f=$(g).children("a").attr("href");if($(g).hasClass("element")){var b="element"}else{if($(g).hasClass("content")){var b="content"}}$("#tree_drag_container").fadeOut("fast",function(){$("#tree_drag_container").html("");$("#tree_drag_container").hide()});var a=$(".tree_listing_row.hover").find("p.label").first();var e=$(a).children("a").attr("href");mouseButton=0;$(".tree_listing_row:not(.dropable)").addClass("dropable");$(".tree_listing_row.hover").removeClass("hover");if(b=="content"){var d="/backend/content/xhr_write_content_parent"}else{if(b=="element"){var d="/backend/content/xhr_write_element_parent"}}if(!e||!f){return null}$("#blocker").fadeIn("fast");$.post(d,{parent_id:e,child_id:f},function(h){if(h.done==true){$.post("/backend/content/xhr_render_tree_unfold",{request:b,id:f},function(i){$("#tree_listing_1").html(i.html);$("#blocker").stop().fadeOut("fast")},"json")}else{$("#blocker").stop().fadeOut("fast");showClientWarning(h.message)}},"json")}});$(window).mousemove(function(b){if(mouseButton==1&&$("#tree_drag_container").children().length>0){$("#tree_drag_container:hidden").fadeIn("fast");$("#tree_drag_container").css("top",(b.pageY-offsetY)+"px");$("#tree_drag_container").css("left",(b.pageX-offsetX)+"px");var a=b.pageY;var c=b.pageX;$(".tree_listing_row.dropable:not(.dragging)").each(function(){var e=$(this).offset().top;var f=$(this).offset().left+$(this).outerWidth();var d=$(this).offset().top+$(this).outerHeight();var g=$(this).offset().left;if(a>e&&c<f&&a<d&&a>g){$(this).addClass("hover")}else{$(this).removeClass("hover")}})}});$(".tree_listing_row").live("mousedown",function(b){b.preventDefault();if($(this).parent("#tree_parent_1").length>0){return null}var c=$(this).offset();offsetY=b.pageY-c.top;offsetX=b.pageX-c.left;$(this).removeClass("dropable");var a=$(this).clone();$(a).addClass("dragging");$(a).children().addClass("dragging");$("#tree_drag_container").html(a)})});var offsetY=0;var offsetX=0;var mouseButton=0;