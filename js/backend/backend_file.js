$(function(){$("body").click(function(){$("form.label").find("input.edit[type='text']").each(function(){$(this).removeClass("edit")})});(function(a){a.fn.extend({update_listing:function(c,d,b){var e=this;a(e).children(".loading").fadeIn("fast");a.post("/backend/file/xhr_render_contents",{path:c,parent:a.getUrlVar("parent")},function(i){try{if(i.done==true){a(e).html(i.html);if(a(e).find("a.item.block.current").length>0){var g=a(e).find("a.item.block.current").first();var f=a(g).next(".item_details").html();a("#current_file_details").html(f).show("fast")}else{a("#current_file_details").hide("fast",function(){a(this).html("")})}if(b){a.post("/backend/file/xhr_render_tree_unfold",{path:c},function(j){if(a(".label.folder > a.current").length>0){a(".label.folder > a.current").removeClass("current")}a("#tree_listing_content_root").html(j.html);if(a(".label.folder > a.current").length==0){a(".label.folder > a").first().addClass("current")}},"json")}else{a(".label.folder > a.current").removeClass("current");a('.label.folder > a[href="'+c+'"]').addClass("current")}a("#current_folder_title").html(i.title);a("#current_folder_mkdir").attr("href",i.path);a("#current_folder_upload").attr("href",i.path)}}catch(h){showClientWarning("Erro de comunicação com o servidor")}a(e).children(".loading").stop().fadeOut("fast")},"json")}})})(jQuery);$(".label.folder > a").live("click",function(a){a.preventDefault();var c=$(this).attr("title");var b=$(this).attr("href");$("#file_manager_listing").update_listing(b,c,false)});$("a.item.block").live("click",function(b){b.preventDefault();$("a.item.block.current").removeClass("current");$(this).addClass("current");var a=$(this).next(".item_details").html();$("#current_file_details").html(a).show("fast")});$("a.item.block.directory").live("dblclick",function(a){a.preventDefault();var c=$(this).attr("title");var b=$(this).attr("href");$("#file_manager_listing").update_listing(b,c,true)});$("a.insert").live("click",function(k){k.preventDefault();var g=$(this).next(".action_insert").html();var q=$(this).attr("title");var n=$(this).parents("#current_file_details");var f=$(n).find("span.mime").first().html();var h=$(n).find("span.size").first().html();var j=$(n).find("span.width").first().html();var i=$(n).find("span.height").first().html();var r=$(n).find("span.icon").first().html();if($.getUrlVar("parent")=="tinymce"){FileManagerDialog.insert(g)}else{if($.getUrlVar("parent")=="direct"){var c=$.getUrlVar("identifier");var a=window.opener.$('input[name="'+c+'"]');var p=window.opener.$("input#"+c+"_description");var e=window.opener.$("div#file_item_thumbnail_"+c);var d=window.opener.$("ul#file_details_"+c);var o=window.opener.$(d).find("span.uri");var m=window.opener.$(d).find("span.mime");var b=window.opener.$(d).find("span.size");if($(a)!=null){var l={uri:g,title:q,mime:f,size:h,width:j,height:i,thumbnail:r};$(a).val($.toJSON(l));if($(p).val()==""){$(p).val(q)}$(e).css("background-image",'url("'+r+'")');$(o).html(g);$(m).html(f);$(b).html(h);$(d).show()}window.close()}}});$("a.fold.folder_switch").live("click",function(b){b.preventDefault();var c=$(this).attr("href");var d=$(this).parents(".tree_parent").first().find(".tree_listing").first();var a=$(this);$.post("/backend/file/xhr_render_tree",{path:c},function(f){try{if(f.done==true){$(d).html(f.html);$(d).slideDown("fast","easeInSine");$(a).addClass("unfold");$(a).removeClass("fold")}}catch(e){showClientWarning("Erro de comunicação com o servidor")}},"json")});$("a.unfold.folder_switch").live("click",function(a){a.preventDefault();var b=$(this).parents(".tree_parent").first().find(".tree_listing").first();$(b).slideUp("fast","easeOutSine");$(this).addClass("fold");$(this).removeClass("unfold")});$("#current_folder_upload").live("click",function(a){a.preventDefault();var b=$(this).attr("href");$.post("/backend/file/xhr_render_upload_form",{path:b},function(d){try{if(d.done==true){$("#current_folder_details").after(d.html);$("#upload_form_container_"+d.upload_session_id).show("fast")}}catch(c){showClientWarning("Erro de comunicação com o servidor")}},"json")});$(".fake_upload_link").live("click",function(a){a.preventDefault()});$("input.upload_file").live("mouseenter",function(){var a=$(this).parents(".upload_form_container").first().find(".fake_upload_link");$(this).css("cursor","pointer");$(a).css("text-decoration","underline")});$("input.upload_file").live("mouseleave",function(){var a=$(this).parents(".upload_form_container").first().find(".fake_upload_link");$(this).css("cursor","default");$(a).css("text-decoration","none")});$("input.upload_file").live("change",function(){$(this).parents("form.upload_form").first().submit()});$(".upload_form").live("submit",function(d){var c=$(this);if($(c).find("input.upload_file").val()==""){return false}$(this).parents(".upload_form_container").first().find(".close_upload").removeClass("close_upload").addClass("cancel_upload");var a=$(this).parents(".upload_form_container").first().find(".loading");$(a).fadeIn("fast");var b=$(c).find("input[name='upload_session_id']").val();$(c).everyTime("3s","upload_session_status",function(){$.post("/backend/file/xhr_read_upload_status",{upload_session_id:b},function(g){try{if(g.done==true){$(c).stopTime("upload_session_status");$(a).fadeOut("slow",function(){$(this).remove()});var e=$(c).parents(".upload_form_container").first();$(e).find(".cancel_upload").removeClass("cancel_upload").addClass("close_upload");$(e).find(".fake_upload_link_container").fadeOut("slow",function(){$(this).remove()});$(e).find(".upload_form").fadeOut("slow",function(){$(this).remove()});$(e).find(".close_upload").before(g.html);$(e).find(".uploaded_file_container").fadeIn("fast")}}catch(f){$(c).stopTime("upload_session_status");$(a).fadeOut("slow")}},"json")})});$(".close_upload").live("mouseenter",function(a){$(this).addClass("close_upload_hover")});$(".close_upload").live("mouseleave",function(a){$(this).removeClass("close_upload_hover")});$(".cancel_upload").live("mouseenter",function(a){$(this).addClass("cancel_upload_hover")});$(".cancel_upload").live("mouseleave",function(a){$(this).removeClass("cancel_upload_hover")});$(".cancel_upload").live("click",function(b){b.preventDefault();var a=$(this).parents(".upload_form_container").first();$(a).find("form.upload_form").stopTime("upload_session_status");$(a).find("iframe").attr("src","/backend/file/cancel_upload");$(a).find(".loading").fadeOut("slow");$(this).removeClass("cancel_upload").addClass("close_upload")});$(".close_upload").live("click",function(a){a.preventDefault();$(this).parents(".upload_form_container").first().hide("slow",function(){$(this).remove()})});$("a.uploaded_file").live("click",function(a){a.preventDefault();var c=$(this).attr("title");var b=$(this).attr("href");$("#file_manager_listing").update_listing(b,c,true)});$(".current_item_erase").live("click",function(c){c.preventDefault();var b=$(this).parents("#current_file_details");var f=$(b).find("p.current_file_title").first().html();var d=$(b).find("span.mime").first().html();if(d==null){var a=" e todo seu conteúdo?"}else{var a="?"}if(confirm("Excluir “"+f+"”"+a)==true){var e=$(this).attr("href");$.post("/backend/file/xhr_rm",{path:e},function(h){try{if(h.done==true){$("#file_manager_listing").update_listing(h.path,h.title,true)}}catch(g){showClientWarning("Erro de comunicação com o servidor")}},"json")}});$(".current_item_rename").live("click",function(c){c.preventDefault();var b=$(this).parents("#current_file_details");var e=$(b).find("p.current_file_title").first().html();var d=$(this).attr("href");var a=prompt("Renomear “"+e+"”",e);if(a!=""&&a!=null){$.post("/backend/file/xhr_rename",{path:d,name:a},function(g){try{if(g.done==true){$("#file_manager_listing").update_listing(g.path,g.title,true)}}catch(f){showClientWarning("Erro de comunicação com o servidor")}},"json")}});$("#current_folder_mkdir").live("click",function(b){b.preventDefault();var c=$(this).attr("href");var a=prompt("Nova Pasta","Nova Pasta");if(a!=""&&a!=null){$.post("/backend/file/xhr_mkdir",{path:c,newdir:a},function(e){try{if(e.done==true){$("#file_manager_listing").update_listing(c+"/"+a,a,true)}}catch(d){showClientWarning("Erro de comunicação com o servidor")}},"json")}})});$.extend({getUrlVars:function(){var d=[],c;var a=window.location.href.slice(window.location.href.indexOf("?")+1).split("&");for(var b=0;b<a.length;b++){c=a[b].split("=");d.push(c[0]);d[c[0]]=c[1]}return d},getUrlVar:function(a){return $.getUrlVars()[a]}});