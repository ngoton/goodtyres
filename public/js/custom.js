var preEl ;
var orgBColor;
var orgTColor;
function HighLightTR(el, backColor,textColor){
  //alert(el.id);
  if(typeof(preEl)!='undefined') {
     preEl.bgColor=orgBColor;
     try{ChangeTextColor(preEl,orgTColor);}catch(e){;}
  }
  orgBColor = el.bgColor;
  orgTColor = el.style.color;
  el.bgColor=backColor;

  try{ChangeTextColor(el,textColor);}catch(e){;}
  preEl = el;
  $('.add-field').slideDown(300);
}


function ChangeTextColor(a_obj,a_color){  ;
   for (i=0;i<a_obj.cells.length;i++)
    a_obj.cells(i).style.color=a_color;
}

/**/

function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = "<link rel=\"stylesheet\" type=\"text/css\"  href=\"<?php echo BASE_URL ?>/public/css/style.css\">\n</head><body><div style=\"testStyle\">"+printContents+ "\n</div>\n</body>\n</html>";

     window.print();

     document.body.innerHTML = originalContents;
}


function searchall(page,cot,sapxep){
  var page = 1;
  var cot = cot;
  var sapxep = sapxep;
  
  var faq_search_input = $('#search-input').val();  // Lấy giá trị search của người dùng
  var loc =    $('#chonloc').val();
  var ngaytao = "";

  if($('#chonngaytao') != null)
  {
    var ngaytao = $('#chonngaytao').val();
  }
  if($('#chonngaytaobatdau') != null)
  {
    var ngaytaobatdau = $('#chonngaytaobatdau').val();
  }
  if($('#batdau') != null)
  {
    var batdau = $('#batdau').val();
  }
  if($('#ketthuc') != null)
  {
    var ketthuc = $('#ketthuc').val();
  }
  if($('#sl_status') != null)
  {
    var trangthai = $('#sl_status').val();
  }
  if($('#sl_nv') != null)
  {
    var nv = $('#sl_nv').val();
  }
  if($('#sl_tha') != null)
  {
    var tha = $('#sl_tha').val();
  }
  if($('#sl_na') != null)
  {
    var na = $('#sl_na').val();
  }
  if($('#tu') != null)
  {
    var tu = $('#tu').val();
  }
  if($('#den') != null)
  {
    var den = $('#den').val();
  }

    var dataString = 'keyword='+ faq_search_input+"&limit="+loc+"&page="+ page +"&order_by="+ cot +"&order="+ sapxep+"&ngaytao="+ ngaytao+"&ngaytaobatdau="+ ngaytaobatdau+"&batdau="+ batdau+"&ketthuc="+ ketthuc+"&trangthai="+ trangthai+"&nv="+nv+"&tha="+tha+"&na="+na+"&tu="+tu+"&den="+den; 
    //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
  $.ajax({
            type: "POST",                            // Phương thức gọi là GET
            url: "#",                 // File xử lý
            data: dataString,                       // Dữ liệu truyền vào
            beforeSend:  function() {               // add class "loading" cho khung nhập
                $('input#search-input').addClass('loading');
            },
            success: function(server_response)      // Khi xử lý thành công sẽ chạy hàm này
            {
                $('body').html(server_response);    // Hiển thị dữ liệu vào thẻ div #searchresultdata
                
                //Enable sidebar toggle
                $(document).on('click', "[data-toggle='offcanvas']", function (e) {
                  e.preventDefault();

                  //Enable sidebar push menu
                  if ($(window).width() > (768 - 1)) {
                    if ($("body").hasClass('sidebar-collapse')) {
                      $("body").removeClass('sidebar-collapse').trigger('expanded.pushMenu');
                    } else {
                      $("body").addClass('sidebar-collapse').trigger('collapsed.pushMenu');
                    }
                  }
                  //Handle sidebar push menu for small screens
                  else {
                    if ($("body").hasClass('sidebar-open')) {
                      $("body").removeClass('sidebar-open').removeClass('sidebar-collapse').trigger('collapsed.pushMenu');
                    } else {
                      $("body").addClass('sidebar-open').trigger('expanded.pushMenu');
                    }
                  }
                });
                
                 
                if ($('input#search-input').hasClass("loading")) {      // Kiểm tra class "loading"
                    $("input#search-input").removeClass("loading");     // Remove class "loading"
                }
        //$('#loading').fadeOut(500); 
            }
        });
  
}
function sapxep(page,cot,sapxep){
          var sapxep        = sapxep;
        if(page==""){
          if($('.sort').attr('class') == "sort DESC"){
            $('.sort').removeClass('DESC');
            $('.sort').addClass('ASC');
            sapxep = "ASC";
          }
          else if($('.sort').attr('class') == "sort ASC"){
            $('.sort').removeClass('ASC');
            $('.sort').addClass('DESC');
            sapxep = "DESC";
          }
          else if($('.sort').attr('class') == "sort"){
            
            $('.sort').addClass('DESC');
            sapxep = "DESC";
          }
        }
          //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
          var cot        = cot;
          var keyword = $('#search-input').val();
          var ngaytao = "";
          var loc =    $('#chonloc').val();

          if($('#chonngaytao') !== null)
          {
            var ngaytao = $('#chonngaytao').val();
          }
          if($('#chonngaytaobatdau') != null)
          {
            var ngaytaobatdau = $('#chonngaytaobatdau').val();
          }
          if($('#batdau') != null)
          {
            var batdau = $('#batdau').val();
          }
          if($('#ketthuc') != null)
          {
            var ketthuc = $('#ketthuc').val();
          }
          if($('#sl_status') != null)
          {
            var trangthai = $('#sl_status').val();
          }
          
          
          $.ajax({
            type: "POST", // phương thức gởi đi
            url: "#", // nơi mà dữ liệu sẽ chuyển đến khi submit
            data: "page="+ page +"&order_by="+ cot +"&order="+ sapxep+"&limit="+ loc+"&keyword="+ keyword+"&ngaytao="+ ngaytao+"&ngaytaobatdau="+ ngaytaobatdau+"&batdau="+ batdau+"&ketthuc="+ ketthuc+"&trangthai="+ trangthai, // giá trị post
            success: function(answer){ // if everything goes well
              
              $('body').html(answer); // đặt kết quả trả về từ test.php vào thẻ div success
              //$('#loading').fadeOut(500);

              //Enable sidebar toggle
                $(document).on('click', "[data-toggle='offcanvas']", function (e) {
                  e.preventDefault();

                  //Enable sidebar push menu
                  if ($(window).width() > (768 - 1)) {
                    if ($("body").hasClass('sidebar-collapse')) {
                      $("body").removeClass('sidebar-collapse').trigger('expanded.pushMenu');
                    } else {
                      $("body").addClass('sidebar-collapse').trigger('collapsed.pushMenu');
                    }
                  }
                  //Handle sidebar push menu for small screens
                  else {
                    if ($("body").hasClass('sidebar-open')) {
                      $("body").removeClass('sidebar-open').removeClass('sidebar-collapse').trigger('collapsed.pushMenu');
                    } else {
                      $("body").addClass('sidebar-open').trigger('expanded.pushMenu');
                    }
                  }
                });
                
            }
          });
}

/*
*/

function checkall(class_name, obj) {
    var items = document.getElementsByClassName(class_name);
    if(obj.checked == true) 
    {
      for(i=0; i < items.length ; i++)
        items[i].checked = true;
    }
    else { 
      for(i=0; i < items.length ; i++)
        items[i].checked = false;
    }
}
function del(id)
{
  if($('.add-field') != null)
  {
    $('.add-field').slideUp();
  }
  var r = confirm("Bạn có chắc chắn muốn xóa không?");
  if (r == true){
    //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
    $.post(window.location.href+"/delete", {data: id},
       function(data){
        //alert(data);
        if (data.trim() != 'Bạn không có quyền thực hiện thao tác này') {
          $('tr#'+id).remove(); 
          //$('#loading').fadeOut(500); 
        };
        //$('#loading').fadeOut(500);
        $("html, body").animate({ scrollTop: 0 }, 100);
       
       }); 
  }
}
function delPhoto(id)
{
  var r = confirm("Bạn có chắc chắn muốn xóa không?");
  if (r == true){
    //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
    $.post("#", {data: id},
       function(data){
       $('tr#'+id).remove(); 
       //$('#loading').fadeOut(500); 
       }); 
  }
}

function action(){
  
    var action       = $('#action').attr('value');
    if(action != -1)
    {
      if($('.add-field') !== null)
        {
          $('.add-field').fadeOut();
        }
      var del = [];
      ids = $('input:checkbox.checkbox:checked').map(function() { return del.push(this.value); });
      
       if(action=='delete'){
         var r = confirm("Bạn có chắc chắn muốn xóa không?");
        if (r == true){
          //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
           $.ajax({
            url: window.location.href+"/delete",   
            type: 'POST',   
            data: "xoa="+del,   
            success:function(answer){ 
              for(var i=0; i<del.length; i++)
                 $('tr#'+del[i]).remove();
              //$('#loading').fadeOut(500); 
              $("html, body").animate({ scrollTop: 0 }, 100);
            }
          });
        }
       }
       else if(action=='phathanh'){
         $.ajax({
          url: window.location.href+"/update", 
          type: 'POST',   
          data: "phathanh="+del,   
          success:function(answer){ 
            for(var i=0; i<del.length; i++)
               $('#trangthai_'+del[i]).html('Hiển thị');
            //$('#loading').fadeOut(500); 
          }
        });
       }
       else if(action=='an'){
         $.ajax({
          url: window.location.href+"/update",   
          type: 'POST',   
          data: "an="+del,   
          success:function(answer){ 
            for(var i=0; i<del.length; i++)
               $('#trangthai_'+del[i]).html('Ẩn');
            //$('#loading').fadeOut(500); 
          }
        });
       }
       else if(action=='noibat'){
         $.ajax({
          url: window.location.href+"/update",  
          type: 'POST',   
          data: "noibat="+del,   
          success:function(answer){ 
            for(var i=0; i<del.length; i++)
               $('#tinnoibat_'+del[i]).html('Nổi bật');
            //$('#loading').fadeOut(500); 
          }
        });
       }
       else if(action=='binhthuong'){
         $.ajax({
          url: window.location.href+"/update",   
          type: 'POST',   
          data: "binhthuong="+del,   
          success:function(answer){ 
            for(var i=0; i<del.length; i++)
               $('#tinnoibat_'+del[i]).html('Bình thường');
            //$('#loading').fadeOut(500); 
          }
        });
       }
    }
  
}

function actionPhoto(){
  
    var action       = $('#action').attr('value');
    if(action != -1)
    {
      var del = [];
      ids = $('input:checkbox.checkbox:checked').map(function() { return del.push(this.value); });
      
       if(action=='delete'){
         var r = confirm("Bạn có chắc chắn muốn xóa không?");
        if (r == true){
          //$('#loading').html("<img src='public/images/loading.gif'/>").fadeIn(500);
           $.ajax({
            url: "#",   
            type: 'POST',   
            data: "xoa="+del,   
            success:function(answer){ 
              for(var i=0; i<del.length; i++)
                 $('tr#'+del[i]).remove();
              //$('#loading').fadeOut(500); 
            }
          });
        }
       }
      
    }
  
}
