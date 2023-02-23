var page = require('webpage').create(),
    system = require('system'),
    url = system.args[1]; //достаем параметр, в котором передан наш url страницы, которую мы парсим
    url = url.replace(/^(\/\/|m)/,'https://');
   
    var fs = require('fs');
    var path = system.args[2];
    var request_path = system.args[4];
    //var path = 'page_mo.html'
    page.settings.userAgent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/74.0.3729.169 Chrome/74.0.3729.169 Safari/537.36';
    
    page.onResourceRequested = function(request,networkdata) {
      //console.log('Request ' + JSON.stringify(request, undefined, 4));
      //fs.write('/var/www/reestr_post/resp.txt', JSON.stringify(networkdata) , 'a');
      
      txt = JSON.stringify(request, undefined, 4);
      //fs.write('requests.txt', txt , 'a');
      if (txt.match(/windowName/ig))
      {
            //fs.write('/var/www/reestr_post/requests.txt', txt , 'a');
            fs.write(request_path, txt , 'a');
            console.log("\nrequest saved!");
            var content = page.content;
            fs.write(path,content,'w');            
                        
            
            phantom.exit();    
      }
      
    };
    /*
    page.onResourceReceived = function (response) {
        //console.log('Receive ' + JSON.stringify(response, undefined, 4));
        txt = JSON.stringify(response);
        fs.write('/var/www/reestr_post/resp.txt', txt , 'a');
    };
    */

    //var CookieJar = "cookiejar.json";
    var CookieJar = system.args[3];
    var pageResponses = {};
    page.onResourceReceived = function(response) {
        pageResponses[response.url] = response.status;
        fs.write(CookieJar, JSON.stringify(phantom.cookies), "w");
    };
    if(fs.isFile(CookieJar))
    {
        Array.prototype.forEach.call(JSON.parse(fs.read(CookieJar)), function(x){
            phantom.addCookie(x);
        });
    }

     page.onConsoleMessage = function(msg) {
      system.stderr.writeLine( 'console: ' + msg );
    };

    page.viewportSize = {
      width: 800,
      height: 1800
    };

    page.clipRect = {
      top: 0,
      left: 0,
      width: 800,
      height: 1800
    };

function CheckAuth()
{
    console.log("\nпроверка авторизации!");
    //page.render('1_auth_check.png');
    res = page.evaluate(function() {
        var auth = false;
        var hrfs = document.getElementsByClassName("v-button-caption");
        capts = Array.prototype.slice.call(hrfs);
        console.log("\ncapts="+capts);
        if (capts)
        {
            capts.forEach(function(cptn) {
                var ctxt = cptn.innerHTML;
                console.log("\ncptn="+ctxt);
                if (ctxt.match(/войти/ig))
                {
                    console.log("\n требуется авторизация!");
                    auth = false;
                }
                if (ctxt.match(/заявки/ig))
                {
                    console.log("\n уже вошли!");
                    auth = true;
                }
                console.log("\niauth="+auth);       
            });
        }  
        console.log("\nfnauth="+auth);                                
        return auth;
    });
    return res;
}


function AuthProcess()
{
     var coords_input1 = page.evaluate(function() {
        var inp1 = document.getElementsByClassName("v-textfield")[0];
        if (inp1)
        {
            var c_x = inp1.getBoundingClientRect().left;
            var c_y = inp1.getBoundingClientRect().top;
        }
        else
        {
            console.log("\npage_load_error");
            phantom.exit();
            var c_x = 0;
            var c_y = 0;
        }
        return {
             x: c_x,
             y: c_y
         };
     });
     page.sendEvent('click', coords_input1.x + 10, coords_input1.y + 5);    
     //page.sendEvent('keypress', page.event.key.Enter, null, null, 0);
     console.log("\ninsert key...");
     
     setTimeout(function() { //автоматическое заполнение полей
         page.evaluate(function() {
            var inp1 = document.getElementsByClassName("v-textfield")[0];
            inp1.focus();
            inp1.click();
            inp1.value = 'f628a54b-1e78-496f-88e5-93e52407b921';
         });
         console.log('\ninp(x,y)='+coords_input1.x+','+coords_input1.y); 
         page.sendEvent('click', coords_input1.x + 10, coords_input1.y + 5);    
         console.log('\n inp_click!');
         //page.render('1_auth_page_1_start.png');
         setTimeout(function() {  //вход
              console.log("\nfill key...");
              page.sendEvent('click', coords_input1.x + 20, coords_input1.y + 10);      
              //page.render('1_auth_page_2.png');
              
              var coords = page.evaluate(function() { //координаты кнопки вход
                  document.getElementsByClassName("v-textfield")[0].dispatchEvent(new Event('change'));  
                  console.log('\nval2='+document.getElementsByClassName("v-textfield")[1].value);  
                  var btn = document.getElementsByClassName("v-button-normalButton")[0];                          btn.style.backgroundColor = "#AA0000";
                  return {
                         x: btn.getBoundingClientRect().left,
                         y: btn.getBoundingClientRect().top
                  };
              });
              console.log('\nbtn(x,y)='+coords.x+','+coords.y); 
              page.sendEvent('click', coords.x + 20, coords.y + 10);    
              //page.sendEvent('click', 340, 420);    
              console.log('\nbtn_click!');
              setTimeout(function() { //вывод результатов
                  //page.render('1_auth_page_3_end.png');
             }, 8000);
             //phantom.exit();
         }, 3000);
     },1000);
}

function SendOrder()
{
    var first_cad_crd = page.evaluate(function() {
        var fk = document.getElementsByClassName("v-table-cell-content-cadastral_num")[0];
        fk.click();
        return {
                 x: fk.getBoundingClientRect().left,
                 y: fk.getBoundingClientRect().top
                };
    });
    if (first_cad_crd)
    {
        page.sendEvent('click', first_cad_crd.x + 100,  first_cad_crd.y + 35);   
        setTimeout(function() {
            //page.render('1_obj_srch_6_click.png');
            var caption_coord = page.evaluate(function(){
                var cpt_el = document.getElementsByClassName("v-embedded v-embedded-image")[0];
                return {
                     x: cpt_el.getBoundingClientRect().left,
                     y: cpt_el.getBoundingClientRect().top,
                };
            });
            page.clipRect = {
                top:    caption_coord.y-20,
                left:   caption_coord.x+26,
                width:  180,
                height: 50 
            };
            //page.render('capture.png'); 
            page.clipRect = {
                top: 0,
                left: 0,
                width: 800,
                height: 1800
            };
            console.log('\ncaption_crd(x,y)='+caption_coord.x+','+caption_coord.y); 
            
            
            page.includeJs('http://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js', function ()           {
                var h1 = page.evaluate(function() {
                    $.ajax({
                        async: false, // this
                        url: 'http://127.0.0.1/phantom_key/ajax.php',
                        //data: { filename: 'C:\\wamp\\www\\images\\0.png' },
                        type: 'get',
                        success: function (output) {
                            console.log("\nres1="+output);
                            prs = JSON.parse(output);
                            if (prs)
                            {
                                console.log(" <=> "+ prs.word);
                                //var cpt_inp = document.getElementsByClassName("v-textfield")[0];
                                var cpt_inp = document.getElementsByClassName("v-textfield-srv-field")[0];
                                cpt_inp.focus();
                                cpt_inp.click();
                                cpt_inp.setAttribute('data',prs.word);
                                cpt_inp.value = prs.word;
                                cpt_inp.style.backgroundColor = "#AAAAAA";
                            }
                            else
                            {
                                console.log("\ncaptcha error!");
                                phantom.exit();
                            }
                        },
                    });
                    return "";
                });
                console.log("\nh1="+h1);
            });
            setTimeout(function() {
                //console.log("\nword="+prs.word);
                //page.render('1_obj_srch_7_captn.png');
                pwd = page.evaluate(function(){
                    var cpt_inp = document.getElementsByClassName("v-textfield-srv-field")[0];
                    cpt_inp.focus();
                    console.log("\n inp_val="+cpt_inp.value);
                    console.log("\n inp_data="+cpt_inp.getAttribute('data'));
                    pwd = cpt_inp.getAttribute('data');
                    return pwd;
                });
                for (var i = 0; i < pwd.length; i++) {
                    console.log(" "+pwd[i]);
                    page.sendEvent('keypress', pwd[i], null, null, 0);
                }
                //page.render('1_obj_srch_7_captn1.png');
                
                var btn_coord = page.evaluate(function(){
                    var res = false;
                    var cpt_inp = document.getElementsByClassName("v-textfield-srv-field")[0];
                    cpt_inp.focus();
                    cpt_inp.click();
                    var hrfs = document.getElementsByClassName("v-button-caption");
                    capts = Array.prototype.slice.call(hrfs);
                    if (capts)
                    {
                        capts.forEach(function(cptn) {
                            var ctxt = cptn.innerHTML;
                            //console.log("\ncptn="+ctxt);
                            if (ctxt.match(/^отправить\s+запрос/ig))
                            {
                                res = {
                                             x: cptn.getBoundingClientRect().left,
                                             y: cptn.getBoundingClientRect().top
                                      };
                                cptn.style.backgroundColor = "#00AA00";
                            }
                        });
                    }
                    else
                    {
                        console.log("\nempty_capts!");       
                    }
                    return res;  
                });
                page.sendEvent('click', btn_coord.x + 20, btn_coord.y + 10);    
                console.log('\nbtn_send_ord_click!');
                setTimeout(function() { //отправить заявку
                      //page.render('1_obj_srch_8_enter.png');
                }, 8000);
            },10000);
            
        },5000);
    }
    
}

function SetMyOrder()
{
    phantom.exit();
    var res = false;
    console.log("\nstart_SetMyOrders!");   
    //координаты вкладки поиска
    coords_obj_srch = page.evaluate(function() {
        var res = false;
        var hrfs = document.getElementsByClassName("v-button-caption");
        capts = Array.prototype.slice.call(hrfs);
        if (capts)
        {
            capts.forEach(function(cptn) {
                var ctxt = cptn.innerHTML;
                //console.log("\ncptn="+ctxt);
                if (ctxt.match(/поиск/ig))
                {
                    res = {
                                 x: cptn.getBoundingClientRect().left,
                                 y: cptn.getBoundingClientRect().top
                          };
                    cptn.style.backgroundColor = "#00AA00";
                }
            });
        }
        else
        {
            console.log("\nempty_capts!");       
        }
        return res;  
    });
    
    if (coords_obj_srch)
    {                          
        //console.log('\nords(x,y)='+coords_obj_srch.x+','+coords_obj_srch.y); 
        page.sendEvent('click', coords_obj_srch.x + 20, coords_obj_srch.y + 10); 
        setTimeout(function() {
              //page.render('1_obj_srch_1_start.png');
              //координаты инпута кадастра, установка значения
              сadinp_crd = page.evaluate(function() {
                    var inp1 = document.getElementsByClassName("v-textfield")[0];
                    inp1.focus();
                    inp1.click();
                    inp1.value = '77:07:0005001:6894'; //orig
                    //inp1.value = '50:20:0000000:19177';
                    return {
                             x: inp1.getBoundingClientRect().left,
                             y: inp1.getBoundingClientRect().top
                      };
              });
              if (!сadinp_crd) { 
                  console.log("\n not loaded search fields!");
                  phantom.exit();
              }
              //координаты селекта региона, установка значения
              selinp_crd = page.evaluate(function() {
                    var selinp = document.getElementsByClassName("v-filterselect-input")[0];
                    //selinp.focus();
                    //selinp.click();
                    //selinp.value = 'Мо';
                    //selinp.click();
                    return {
                             x: selinp.getBoundingClientRect().left,
                             y: selinp.getBoundingClientRect().top
                      };
              });
              //координаты кнопки Поиск
              var coord_srch_btn = page.evaluate(function() {          
                    var hrfs = document.getElementsByClassName("v-button-caption");
                    capts = Array.prototype.slice.call(hrfs);
                    //console.log("\ncapts="+capts);
                    if (capts)
                    {
                        var rs = false;
                        capts.forEach(function(cptn) {
                            var ctxt = cptn.innerHTML;
                            //console.log("\ncptn="+ctxt);
                            if (ctxt.match(/найти/ig))
                            {
                                rs = {
                                             x: cptn.getBoundingClientRect().left,
                                             y: cptn.getBoundingClientRect().top
                                      };
                                cptn.style.backgroundColor = "#00AA00";
                            }
                        });
                        return rs;
                    }
                    else
                    {
                        console.log("\nempty_capts!"); 
                    }
                    return false;      
              });
              //клик по инпуту кадастра
              page.sendEvent('click', сadinp_crd.x + 15,  сadinp_crd.y + 10);
              setTimeout(function() {
                  //console.log('\nselinp_crd(x,y)='+selinp_crd.x+','+selinp_crd.y); 
                  //клик по вводу селекта региона
                  page.sendEvent('click', selinp_crd.x + 15,  selinp_crd.y + 10); 
                  //page.sendEvent('click', selinp_crd.x + 330, selinp_crd.y + 10); 
                  //page.render('1_obj_srch_2_ins_kad.png');
                  setTimeout(function() {
                      //вставка текста региона
                      page.evaluate(function() {
                          var selinp = document.getElementsByClassName("v-filterselect-input")[0];
                          selinp.focus();
                          selinp.click();
                          selinp.value = 'Москв';
                      });
                      //page.render('1_obj_srch_3_sel_insert.png');
                      //ввод последней буквы с клавиатуры
                      page.sendEvent('keypress', 1072, null, null, 0);
                      setTimeout(function() {
                            //page.render('1_obj_srch_4_sel_ins_last_key.png');
                            page.evaluate(function() {
                                var sels = document.getElementsByClassName("gwt-MenuItem");
                                asels = Array.prototype.slice.call(sels);
                                asels.forEach(function(asl) {
                                     var astxt = asl.innerHTML;
                                      console.log("\nasl="+astxt);
                                      if (astxt.match(/москва/ig))
                                      {
                                          asl.focus();
                                          asl.click();
                                      }
                                });
                            });
                            //page.render('1_obj_srch_4_sel_ins_last_key_fokus.png');
                            //клик по первому выпавшему значению 
                            page.sendEvent('click', selinp_crd.x + 40,  selinp_crd.y + 40);       
                            if (coord_srch_btn)
                            {
                                //page.sendEvent('click', selinp_crd.x + 20,  selinp_crd.y + 10);
                                //page.sendEvent('keydown', 13, null, null, 0);
                                //page.sendEvent('keypress', 13, null, null, 0);
                                //page.sendEvent('keyup', 13, null, null, 0);
                               //фокус на вводе селекта региона
                                 /*page.evaluate(function() {
                                    var selinp = document.getElementsByClassName("v-filterselect-input")[0]; 
                                    selinp.focus();
                                });
                                */ 
                                
                                setTimeout(function() {
                                    //page.render('1_obj_srch_4_sel_zclick.png');
                                    //клик по инпуту кадастра
                                    page.sendEvent('click', сadinp_crd.x + 15,  сadinp_crd.y + 10);
                                    //enter с клавиатуры
                                    page.sendEvent('keypress', page.event.key.Enter);
                                    //page.render('1_obj_srch_4_z_enter.png');
                                    
                                    setTimeout(function() {
                                        //page.render('1_obj_srch_5_found.png');
                                        if (page.content.match(/Найдено\s+объектов/ig))
                                        {
                                            SendOrder();
                                        }
                                        else
                                        {
                                            console.log("\nnot found objects!");
                                            console.log("\n ==2==!");
                                            //клик по инпуту кадастра
                                            page.sendEvent('click', сadinp_crd.x + 15,  сadinp_crd.y + 10);
                                            //enter с клавиатуры
                                            page.sendEvent('keypress', page.event.key.Enter);
                                            //page.render('1_obj_srch_5_4_z_enter.png');
                                            setTimeout(function() {
                                                //page.render('1_obj_srch_5_5_found.png');
                                                if (page.content.match(/Найдено\s+объектов/ig))
                                                {
                                                    SendOrder();
                                                }
                                                else
                                                {
                                                    console.log("\nnot found objects!");
                                                    phantom.exit();
                                                }
                                            },40000);
                                        }
                                    },40000);
                                
                                },3000); 
                               
                             }
                             else
                             {
                                 console.log("\nempty osrchbtn coords!"); 
                             }
                             
                             
                     },5000);
                  },2000); 
              },1000); 
        },8000); 
        res = true;      
    }
    else
    {
        //не найден таб поиска
        console.log('\nempty coords_my_ord!');
        res = false; 
    }
    return res;    
}

page.open(url, function (status) {
    try {
        if (status !== "success") {
            console.log("Unable to access network");
            phantom.exit();
        } else {
            //do some stuff with the DOM
            //my
            
           // page.injectJs('jquery.js');    //подключаем jquery.js
            setTimeout(function () {
                console.log("\nERROR_EXIT 120 SEC!");
                phantom.exit();
            }, 200000); //от зависания
            
             //page.render('1_a_page_1_start.png');
             console.log("\nзагрузка ajax...");
             setTimeout(function() {  //ожидание загрузки ajax-окна
                 var auth = CheckAuth();  
                 console.log("\nauth="+auth);
                 if (auth)
                 {
                    SetMyOrder();
                    setTimeout(function() {
                        console.log("\nsave_results!");
                        var content = page.content;
                        fs.write(path,content,'w');            
                        //page.render('1_page_result_end.png');
                        phantom.exit();
                    },150000);
                 }
                 else
                 {
                     AuthProcess();
                     setTimeout(function() { 
                          var auth = CheckAuth();  
                          console.log("\nauth="+auth);
                          if (auth)
                          {
                              SetMyOrder();
                                setTimeout(function() {
                                    console.log("\nsave_results2!");
                                    var content = page.content;
                                    fs.write(path,content,'w');            
                                    //page.render('1_page_result_end2.png');
                                    phantom.exit();
                                },150000);
                          }
                          else
                          {
                              console.log("\n STOPPED! NOT LOADED ORDERS!");
                              phantom.exit();
                          }
                     },30000);
                 }
                 
            }, 6000);
        }        
    } catch (ex) {
        var fullMessage = "\nJAVASCRIPT EXCEPTION";
        fullMessage += "\nMESSAGE: " + ex.toString();
        for (var p in ex) {
            fullMessage += "\n" + p.toUpperCase() + ": " + ex[p];
        }
        console.log(fullMessage);
        phantom.exit();
    }
    
    
    
    
    
    
});
