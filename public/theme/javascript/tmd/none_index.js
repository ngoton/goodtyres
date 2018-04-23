$(document).ready(function(){
    $(".hadsub-menu").hover(function(){
        $(this).find(".submenu-lv1").toggleClass("show");
        $(this).find("h2").toggleClass("redColor");
        $(this).toggleClass("borderBt");
    });

    $(".main-menu").hover(function(){
        $(".main_menu_dropdown").toggleClass("show");   
    })
    
});

// brand-list
$(document).ready(function() {
    $('#brandlist').carousel({
        interval: 10000
    })
});

// top panel fixed

$(".main-menu-fixed").html($(".main-menu").html());
$(".search-top-fixed").html($(".search-top").html());
$(".cart-fixed").html($(".cart-top").html());
$(".main-menu-fixed").hover(function(){
    $(this).find($(".main_menu_dropdown_fixed")).toggleClass("show");
});

$(window).scroll(function () {
    try {
        if ($(window).scrollTop() >= $('.main-content').offset().top) {
            $('.top-panel-fixed-wrp').show();
            $(".main-menu-fixed").find($(".main_menu_dropdown")).addClass("main_menu_dropdown_fixed");
            $(".main-menu-fixed").find((".submenu-lv1")).css("margin-right","10px");


          
          $(':input[name="txtHeaderSearch"]').click(function(){
            $('.row-tyre-canvas').slideDown(500);
            //$('.row-tyre-canvas').addClass('active');
          });
          
          $('.row-tyre-canvas .close-btn').click(function(){
            $('.row-tyre-canvas').slideUp(500);
            //$('.row-tyre-canvas').removeClass('active');
          });

          $("#tyre-position-select").change(function() {
              $("#tyre-position-image").attr("src", "/public/images/truck-" + $(this).val() + "-icon.png")
          })
          $("#tyre-rim-select").change(function(){
            var a = "";
            var val = $(this).val();
            if (val == "R16") {
              a += '<option value="6.50">6.50</option>';
              a += '<option value="7.00">7.00</option>';
              a += '<option value="7.50">7.50</option>';
              a += '<option value="8.25">8.25</option>';
            }
            else if (val == "R17.5") {
              a += '<option value="9.50">9.50</option>';
              a += '<option value="215/75">215/75</option>';
              a += '<option value="235/75">235/75</option>';
              a += '<option value="225/80">225/80</option>';
              a += '<option value="235/80">235/80</option>';
            }
            else if (val == "R20") {
              a += '<option value="8.25">8.25</option>';
              a += '<option value="9.00">9.00</option>';
              a += '<option value="10.00">10.00</option>';
              a += '<option value="11.00">11.00</option>';
              a += '<option value="12.00">12.00</option>';
              a += '<option value="14.00">14.00</option>';
            }
            else if (val == "R22.5") {
              a += '<option value="10">10</option>';
              a += '<option value="11">11</option>';
              a += '<option value="12">12</option>';
              a += '<option value="13">13</option>';
              a += '<option value="385/65">385/65</option>';
              a += '<option value="255/70">255/70</option>';
              a += '<option value="275/70">275/70</option>';
              a += '<option value="315/70">315/70</option>';
              a += '<option value="295/75">295/75</option>';
              a += '<option value="275/80">275/80</option>';
              a += '<option value="295/80">295/80</option>';
              a += '<option value="315/80">315/80</option>';
            }
            else if (val == "R24") {
              a += '<option value="12.00">12.00</option>';
              a += '<option value="325/95">325/95</option>';
            }
            else if (val == "R24.5") {
              a += '<option value="11">11</option>';
              a += '<option value="285/75">285/75</option>';
            }

            $("#tyre-width-select").html("<option></option>"+a);
          });


        } else {
            $('.top-panel-fixed-wrp').hide();
            $(".main-menu-fixed").find($(".main_menu_dropdown")).removeClass("main_menu_dropdown_fixed");
            $(".main-menu-fixed").find((".submenu-lv1")).css("margin-right","0");
        }
    } catch (e) {
    }
});
