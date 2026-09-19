// Vitrine — en-tête au scroll, menu mobile, révélations, carte de contact, formulaires.
(function(){
  var header=document.getElementById('header');
  var scrollTopBtn=document.getElementById('scrollTopBtn');
  var onScroll=function(){
    header.classList.toggle('scrolled',window.scrollY>12);
    if(scrollTopBtn)scrollTopBtn.classList.toggle('is-visible',window.scrollY>400);
  };
  onScroll();window.addEventListener('scroll',onScroll,{passive:true});

  // behavior omis : suit scroll-behavior défini sur <html> (smooth, sauf
  // prefers-reduced-motion:reduce où il repasse déjà à "auto" en CSS).
  if(scrollTopBtn)scrollTopBtn.addEventListener('click',function(){window.scrollTo({top:0});});

  var toggle=document.getElementById('navToggle');
  var menu=document.getElementById('mobileMenu');
  var setMenu=function(open){
    menu.classList.toggle('open',open);
    toggle.setAttribute('aria-expanded',open?'true':'false');
    toggle.setAttribute('aria-label',open?'Fermer le menu':'Ouvrir le menu');
    document.body.style.overflow=open?'hidden':'';
  };
  toggle.addEventListener('click',function(){setMenu(!menu.classList.contains('open'));});
  menu.addEventListener('click',function(e){if(e.target.tagName==='A')setMenu(false);});

  // Compteur animé (chiffres du CMS/backend) : part de 0 et grandit jusqu'à la valeur
  // réelle déjà présente dans le HTML — celle-ci n'est jamais recalculée ni remplacée,
  // seulement affichée progressivement, et le texte final est toujours exactement le
  // texte d'origine (aucun risque de désynchronisation avec la BDD/le CMS).
  var runCountUp=function(el){
    if(el.dataset.counted)return;
    el.dataset.counted='1';
    var raw=el.textContent.trim();
    var m=raw.match(/\d[\d\s  .,]*\d|\d/);
    if(!m)return; // pas de nombre dans ce texte (ex. valeur CMS non numérique) : on n'y touche pas
    var target=parseInt(m[0].replace(/\D/g,''),10);
    if(!isFinite(target)||target<=0)return;
    var prefix=raw.slice(0,m.index);
    var suffix=raw.slice(m.index+m[0].length);
    var fmt=window.Intl&&Intl.NumberFormat?new Intl.NumberFormat('fr-FR'):null;
    var duration=Math.min(1400,Math.max(600,300+target/40));
    var start=null;
    var frame=function(now){
      if(start===null)start=now;
      var p=Math.min(1,(now-start)/duration);
      var eased=1-Math.pow(1-p,3);
      var value=Math.round(eased*target);
      el.textContent=prefix+(fmt?fmt.format(value):value)+suffix;
      if(p<1){requestAnimationFrame(frame);}else{el.textContent=raw;}
    };
    requestAnimationFrame(frame);
  };

  if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: no-preference)').matches){
    var io=new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if(en.isIntersecting){
          io.unobserve(en.target);
          // Petit délai avant de révéler : un bloc déjà visible au chargement (ex. les
          // stats du hero, au-dessus de la ligne de flottaison) sinon se déclenche
          // quasi instantanément et le compteur finit avant que l'œil n'ait le temps
          // de le voir démarrer.
          setTimeout(function(){
            en.target.classList.add('in');
            en.target.querySelectorAll('.count-up').forEach(runCountUp);
          },200);
        }
      });
    },{threshold:.12,rootMargin:'0px 0px -8% 0px'});
    document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});
  }
  // prefers-reduced-motion:reduce → aucun IntersectionObserver instancié, les .count-up
  // gardent simplement leur valeur réelle déjà rendue côté serveur (aucune animation).

  // Compte à rebours des cartes événement (page /evenements) — même principe que le
  // compteur du formulaire d'inscription (data-deadline en ISO 8601, recalculé côté
  // client), simplifié en jours/heures/minutes pour rester sobre sur plusieurs cartes
  // affichées en même temps. Un rafraîchissement de la minute suffit (pas besoin des
  // secondes ici) ; passé l'échéance, remplace le compte à rebours par un message fixe.
  document.querySelectorAll('[data-event-deadline]').forEach(function(box){
    var deadline=new Date(box.getAttribute('data-event-deadline')).getTime();
    var ringsEl=box.querySelector('.event-countdown');
    var endedEl=box.querySelector('.event-countdown-ended');
    var values={};
    box.querySelectorAll('[data-unit]').forEach(function(el){values[el.getAttribute('data-unit')]=el;});

    function pad(n){return String(n).padStart(2,'0');}

    function tick(){
      var diff=deadline-Date.now();
      if(diff<=0){
        clearInterval(interval);
        if(ringsEl)ringsEl.hidden=true;
        if(endedEl)endedEl.hidden=false;
        return;
      }
      var totalMinutes=Math.floor(diff/60000);
      if(values.days)values.days.textContent=pad(Math.floor(totalMinutes/1440));
      if(values.hours)values.hours.textContent=pad(Math.floor((totalMinutes%1440)/60));
      if(values.minutes)values.minutes.textContent=pad(totalMinutes%60);
    }

    tick();
    var interval=setInterval(tick,30000);
  });

  // Empêche toute double-soumission : bouton désactivé + petit spinner le temps de
  // l'envoi. Formulaires classiques (rechargement de page à la réponse) — le bouton
  // reste désactivé jusqu'à la navigation suivante, ce qui suffit à bloquer un second clic.
  document.querySelectorAll('.contact-form, .newsletter form').forEach(function(form){
    form.addEventListener('submit', function(){
      var btn=form.querySelector('button[type=submit]');
      if(!btn||btn.disabled)return;
      btn.disabled=true;
      btn.classList.add('is-loading');
      var spin=document.createElement('span');
      spin.className='spinner';
      spin.setAttribute('aria-hidden','true');
      btn.appendChild(spin);
    });
  });

  // Carte de contact (Leaflet auto-hébergé, fond OpenStreetMap). Coordonnées pilotées
  // par le CMS (section « pied ») — si absentes, le conteneur n'est simplement pas rendu
  // par le Blade (cf. contact.blade.php), donc rien à initialiser ici.
  var mapEl=document.getElementById('contactMap');
  if(mapEl){
    // Le CSS de Leaflet n'est chargé qu'ici, avec le JS — jamais sur les 5 autres pages
    // vitrine qui n'ont pas de carte (performance : ~10 Ko de CSS en moins ailleurs).
    import('leaflet/dist/leaflet.css');
    import('leaflet').then(function(mod){
      var L=mod.default||mod;
      // Les icônes par défaut de Leaflet référencent des chemins relatifs qui ne
      // survivent pas au bundling Vite — on les réimporte explicitement.
      Promise.all([
        import('leaflet/dist/images/marker-icon-2x.png'),
        import('leaflet/dist/images/marker-icon.png'),
        import('leaflet/dist/images/marker-shadow.png'),
      ]).then(function(icons){
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
          iconRetinaUrl: icons[0].default,
          iconUrl: icons[1].default,
          shadowUrl: icons[2].default,
        });

        var lat=parseFloat(mapEl.dataset.lat);
        var lng=parseFloat(mapEl.dataset.lng);
        var zoom=parseInt(mapEl.dataset.zoom,10)||14;
        var label=mapEl.dataset.label||'';

        var map=L.map(mapEl,{scrollWheelZoom:false}).setView([lat,lng],zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{
          maxZoom:19,
          attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
        }).addTo(map);
        var marker=L.marker([lat,lng]).addTo(map);
        if(label)marker.bindPopup(label);

        // La molette ne zoome qu'au clic (évite de piéger le scroll de page) ; le tactile reste libre.
        mapEl.addEventListener('click',function(){map.scrollWheelZoom.enable();});
        mapEl.addEventListener('mouseleave',function(){map.scrollWheelZoom.disable();});
      });
    });
  }
})();
