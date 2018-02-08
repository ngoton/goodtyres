$(function () {
      var w = $("#container").width();

      $(".container").width(w);
      $(".navbar-inner").width(w);
      $(".navbar").width(w);
      $("#container").width(w);
      $(".fix_img").width(w);
      $(".footer-nav").width(w);
      $("footer").width(w);

        setNavigation();
        $('.home').click(function(){

          var hrefval = $(this).attr("data");
          
              if(hrefval != "#") {
                closeSidepage('.slide-content');
                openSidepage(hrefval);

                $('.nav li').removeClass('active');

                $(this).closest('li').addClass('active');
                $(this).closest('li').parents('li').addClass('active');

                $("html, body").animate({ scrollTop: 0 }, 1);
              }

        });

        
        $('.png').click(function(){
          $('#support').animate({
            height: '300px'
          }, 300);
        });
        $('.close').click(function(){
          $('#support').animate({
            height: '22px'
          }, 300);
        });


      
    });


      function openSidepage(id) {
        $(id).css({display: "block"});
        $(id).animate({
          left: '0px'
        }, 500, 'easeOutBack'); 
      }
      
      function closeSidepage(id){
        
        $(id).animate({
          left: '-1500px'
        }, 100, 'easeOutQuint');  
        $(id).css({left: "-1500px",display: "none"});
      }

    function setNavigation() {
        var path = window.location.pathname;
        path = path.replace(/\/$/, "");
        path = decodeURIComponent(path);

        $(".nav a").each(function () {
            var href = "/"+$(this).attr('data');

            if (path === href) {
                $(this).closest('li').addClass('active');
                $(this).closest('li').parents('li').addClass('active');

            }
            if (window.location.hash == $(this).attr("data")) {
              var hrefval = $(this).attr("data");
          
              if(hrefval != "#") {
                closeSidepage('.slide-content');
                openSidepage(hrefval);

                $(this).closest('li').addClass('active');
                $(this).closest('li').parents('li').addClass('active');

                $("html, body").animate({ scrollTop: 0 }, 1);
              }
            }
            
        });

        $(".sidebar-menu a").each(function () {
            var href = "/"+$(this).attr('data');

            if (path === href) {
                $(this).closest('li').addClass('active');
                $(this).closest('li').parents('li').addClass('active');

            }
            if (window.location.hash == $(this).attr("data")) {
              var hrefval = $(this).attr("data");
          
              if(hrefval != "#") {
                closeSidepage('.slide-content');
                openSidepage(hrefval);

                $(this).closest('li').addClass('active');
                $(this).closest('li').parents('li').addClass('active');

                $("html, body").animate({ scrollTop: 0 }, 1);
              }
            }
            
        });

        if (path == "" && window.location.hash == "") {
            closeSidepage('.slide-content');
            openSidepage('#index');

            $('li').first().addClass('active');

        }
    }

    $(document).ready(function () {
            $("#btnExport").click(function () {
                $("#tblExport").battatech_excelexport({
                    containerid: "tblExport"
                   , datatype: 'table'
                });
            });
        });

    /*function flashtext(ele, col) {
            var tmpColCheck = document.getElementById(ele).style.color;

            if (tmpColCheck === 'white') {
                document.getElementById(ele).style.color = col;
            } else {
                document.getElementById(ele).style.color = 'white';
            }
        }

        setInterval(function () {
            flashtext('nfc_count', 'red');
        }, 500); //set an interval timer up to repeat the function

    $(document).ready(function(){
          $(':input').live('focus',function(){
              $(this).attr('autocomplete', 'off');
          });
      });*/
      
    /*$(function(){
      $(".table_data").colResizable({
        liveDrag:true,
        gripInnerHtml:"<div class='grip'></div>", 
        draggingClass:"dragging"
      });
    });*/

    /*var refreshId = setInterval(function(){  
    $.ajax({
      type: "POST",
      url: "<?php echo BASE_URL ?>/admin/notification",
      data: 'data=1', // or any data you want to send
      cache: false,
      success: function(html){
       if (html != parseInt(html, 10)) {
          html = 0;
        }
      $('#nfc_count').text(html).fadeIn('fast'); // response from server side process
      }
    });
    },10000);*/