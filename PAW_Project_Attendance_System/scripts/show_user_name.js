(function(){
  function setUserName(name){
    if(!name) return;
    // try to find existing element
    var el = document.getElementById('userName');
    if(el){ el.textContent = name; return; }
    // otherwise inject into header as a small span on the right
    var header = document.querySelector('header');
    if(!header) return;
    var div = document.createElement('div');
    div.style.marginLeft = '12px';
    div.style.fontWeight = '600';
    div.style.fontSize = '14px';
    div.style.color = 'white';
    div.id = 'userName';
    div.textContent = name;
    // if header uses flex, append to end, otherwise prepend
    header.style.display = header.style.display || 'flex';
    header.style.justifyContent = header.style.justifyContent || 'space-between';
    header.style.alignItems = header.style.alignItems || 'center';
    header.appendChild(div);
  }

  function fetchAndSet(){
    if (window.sessionStorage && sessionStorage.getItem('full_name')) {
      setUserName(sessionStorage.getItem('full_name'));
      return;
    }
    if (window.jQuery) {
      $.ajax({ url: '../api/me.php', type: 'GET', dataType: 'json' })
        .done(function(resp){ if (resp && resp.success && resp.data && resp.data.full_name) { try { sessionStorage.setItem('full_name', resp.data.full_name); } catch(e){} setUserName(resp.data.full_name); } })
        .fail(function(){ /* ignore */ });
    } else {
      // fetch via XHR
      var xhr = new XMLHttpRequest();
      xhr.open('GET','../api/me.php');
      xhr.onreadystatechange = function(){ if(xhr.readyState===4 && xhr.status===200){ try{ var j = JSON.parse(xhr.responseText); if(j && j.success && j.data && j.data.full_name){ try{ sessionStorage.setItem('full_name', j.data.full_name); }catch(e){} setUserName(j.data.full_name);} }catch(e){} }};
      xhr.send();
    }
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') fetchAndSet();
  else document.addEventListener('DOMContentLoaded', fetchAndSet);
})();