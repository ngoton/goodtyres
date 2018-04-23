// JavaScript Document


function writeContentPopup(quantity,sumPrice){
	/* $.ajax({ type: 'POST', 
		url: base_url+"client/get_total_cart",
		data: "", 
		success: function (data){ 
			 var cartArray=jQuery.parseJSON(data);
			
		}, 
		error:function (xhr, ajaxOptions, thrownError){ 
			//alert("Lỗi hệ thống.");
		}
	}); */
	
	 $("#currentQuantityChoose").html($("#quantityBuy").val());
	 $("#countQuantityCartPopup").html(quantity);
	 $("#totalPriceCartPopup").html(sumPrice + " đ");
	
}

function rawCart(showPopup){
	$.ajax({
		  type: 'POST',
		  url:base_url+"/public/theme/client/get_all_cart",
		  data:'',
		  success: function(data){
		    $(".cartTopRightQuantity").html(0);
			$(".cartTopRightContent").html('<li><div class="col-md-12 item-cart">Bạn chưa chọn sản phẩm nào!</div></li>');	
			$("#cartTopRightFix").html($("#cartTopRight").html());
			
			 var cartArray=jQuery.parseJSON(data);
			 var quantity=0;
			 if (cartArray.length>0)
				 {
				 $(".cartTopRightQuantity").html('0');
				 $(".cartTopRightContent").html('');
				 
				
				 var html="";
				 var sumMoney=0;
				for (var i=0;  i<cartArray.length; i++)
				{
					quantity+=parseInt( cartArray[i]["quantity"],10);
					sumMoney+=parseInt( cartArray[i]["total_price"],10);
					
					html+=' <li>';
					html+='    	<div class="col-md-12 item-cart">';
					html+='    		<span class="img-cart"><a href="'+cartArray[i]["link"]+'"><img src="'+cartArray[i]["thumb_url"]+'"/></a></span>';
					html+='    		<span class="title-cart"><a href="'+cartArray[i]["link"]+'">'+cartArray[i]["name"]+'</a></span>';
					html+='    		<span class="quality-cart">Số lượng: '+cartArray[i]["quantity"]+'</span>';
					html+='   		<span class="remove-cart"><a href="javascript:void()" cart="'+cartArray[i]["car_id"]+'" onclick="javascript:removeItemCart(this)"><i class="fa fa-times-circle"></i></a></span>';
					html+='    		<div class="clearFix"></div>'
					html+='    	</div>';
					html+='    </li>';
					
				}
				
				 html+='<li><div class="col-md-12 item-cart"><a class="btn btn-success checkout-btn" href="'+base_url+checkOutUrl+'">Đặt hàng</a></div></li>';
				 $(".cartTopRightContent").html(html);	
				 $(".cartTopRightQuantity").html(addCommas(quantity));
				  $(".cartTopRightContent").attr('style','');
				 $("#cartTopRightFix").html($("#cartTopRight").html());

				 $("#sum_total_cart").html();
				 $("#sum_total_cart").html(addCommas(sumMoney));

				 writeContentPopup(addCommas(quantity),addCommas(sumMoney));

				 if (showPopup==true)
				 {
				 	$('#checkout-detail').modal();
				 }
			}
			
		},
		error:function (xhr, ajaxOptions, thrownError){
			//$("#waiting_box").fadeOut(100);
			//alert(xhr.responseText);
		}    
	});	
}

function pushItemToCart(id,quantity,buynow){
	
	$.ajax({ type: 'POST', 
		url: base_url+"/public/theme/client/cart_plus",
		data: "id="+id+"&quantity="+quantity, 
		success: function (data){ 
			if (buynow==true)
			{
				window.location=base_url+"thanh-toan/step-1.html";	
			}
			else
			{
				rawCart(true);
				
			}
		}, 
		error:function (xhr, ajaxOptions, thrownError){ 
			//alert("Lỗi hệ thống.");
		}
	});
}

function removeItemCart(me){
	var id=$(me).attr("cart");
	$.ajax({ type: 'POST', 
		url: base_url+"/public/theme/client/cart_remove_item",
		data: "id="+id, 
		success: function (data){ 
			rawCart(false);
		}, 
		error:function (xhr, ajaxOptions, thrownError){ 
			//alert("Lỗi hệ thống.");
		}
	});
}


$(document).ready(function(e) {
	$("#addToCart").click(function(){
		var productId=$(this).attr('rel');
		var quantity=$("#quantityBuy").val();
		var check=true;
		if (check==true)
		{
			pushItemToCart(productId,quantity,false);
		}
		return false;
	});
});



$(document).ready(function(e) {
    rawCart(false);
});

