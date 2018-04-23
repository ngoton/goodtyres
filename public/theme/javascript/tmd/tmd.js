/* Header catalogue*/

/* xe may cu */
$("#widget_extended_xemaycu").hide();
function writeBannerXemaycu(){
	 $.ajax({ type: 'POST', 
		url: base_url+"/public/theme/extended/json/xecu",
		data: "", 
		success: function (data){ 
		
			 var dataArray=jQuery.parseJSON(data);
			 if (dataArray.length>0)
			 {
				 var html='';
				 for (var i=0 ; i<dataArray.length; i++)
				 {
					 html+='<div class="items-ctb"><div class="img-ctb"><a rel="nofollow" href="'+dataArray[i]["link"]+'" target="_blank"><img src="'+dataArray[i]["thumb"]+'" alt="'+dataArray[i]["name"]+'"/></a></div><div class="title-ctb"><a rel="nofollow" href="'+dataArray[i]["link"]+'" title="'+dataArray[i]["name"]+'">'+dataArray[i]["name"]+' <i>('+dataArray[i]["year"]+')</i></a></div><div class="price-ctb">'+dataArray[i]["price_format"]+' đ</div><div class="clearFix"></div></div>';
				 }
				 $("#widget_extended_xemaycu .items-list-ctb").append(html);
				 $("#widget_extended_xemaycu").show();
			 }
			 else
			 {
				 $("#widget_extended_xemaycu").hide();
			 }
		}, 
		error:function (xhr, ajaxOptions, thrownError){ 
			//alert("Lỗi hệ thống.");
		}
	})	
	return "";
}

$(document).ready(function(e) {
	writeBannerXemaycu();
});