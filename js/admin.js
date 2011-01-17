/**
  * $Id$
  * 
  * Copyright 2008-2010 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

/* SUBMENU */
function admin_submenu(id)
{
	$(".admin_submenu a").removeClass("selected");
	$(".admin_submenu a[name='"+id+"']").addClass("selected");
	$(".subcontents").hide();
	$("#"+id).show();
}

/* DATAMODEL */
function datamodel_opt_add(type, name)
{
	if (type && name && !document.getElementById('opt_'+type+'_'+name))
	{
		$("#opt_"+type).append('<div id="opt_'+type+'_'+name+'"><p style="margin-bottom: 0px;">'+name+' <a href="javascript:;" onclick="opt_del(\''+type+'\',\''+name+'\')" style="color:red;">X</a></p> <p style="margin-top: 0px;"><textarea name="optlist['+type+']['+name+']"></textarea></p>');
	}
}
function datamodel_opt_del(type, name)
{
	$("#opt_"+type+'_'+name).remove();
}

/* DATA QUERY */
function admin_data_query(element)
{
	if (element.value)
		object_list_query($('#datamodel_id').val(), [{'type':$('#q_type').val(), 'value':element.value}], $(element).parent().parent().eq(0));
	else
		object_list_hide($(element).parent().eq(0))
}

/* DATA UPDATE */
function admin_data_id_update_toggle(element)
{
	if (element.form.id.getAttribute('readonly')==null)
		element.form.id.setAttribute('readonly', 'readonly');
	else
		element.form.id.removeAttribute('readonly');
}

/* PAGES */
function page_param_update(name)
{
	var element = document.getElementById('param['+name+'][value]');
	element.name = element.id;
	element = document.getElementById('param['+name+'][update_pos]');
	element.name = element.id;
}
function page_param_add_cancel()
{
	$(".param_add [name]").removeAttr('name');
}
function page_param_add(name)
{
	if (name)
	{
		$("input[id='param["+name+"][value]']").each(function(){
			$(this).attr("name", "param_add[value]");
		});
		$("select[id='param["+name+"][update_pos]']").each(function(){
			$(this).attr("name", "param_add[update_pos]");
		});
		$("input[id='param["+name+"][name]']").each(function(){
			$(this).attr("name", "param_add[name]");
		});
	}
	else
	{
		$(".param_add input[id], .param_add select[id]").each(function(){
			$(this).attr("name", this.id);
		});
	}
}
