(function(){
    const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';
    const SCOPES = 'https://www.googleapis.com/auth/calendar';

    let tokenClient;
    let gapiInited = false;
    let gisInited = false;

    function enableAuthorizeButton(){
        if (!gapiInited || !gisInited) return;
        const btn = document.getElementById('authorize_button');
        if (btn) btn.removeAttribute('disabled');
    }

    // Called by <script onload="gapiLoaded()">
    window.gapiLoaded = function(){
        gapi.load('client', async function(){
            try{
                await gapi.client.init({
                    apiKey: wresslaGCal.apiKey,
                    discoveryDocs: [DISCOVERY_DOC],
                });
                gapiInited = true;
                enableAuthorizeButton();
            }catch(e){
                console.error('gapi init error', e);
            }
        });
    };

    // Called by <script onload="gisLoaded()">
    window.gisLoaded = function(){
        try{
            tokenClient = google.accounts.oauth2.initTokenClient({
                client_id: wresslaGCal.clientId,
                scope: SCOPES,
                callback: function(resp){ /* set per request in handleAuthClick */ },
            });
            gisInited = true;
            enableAuthorizeButton();
        }catch(e){
            console.error('gis init error', e);
        }
    };

    window.handleAuthClick = function(){
        if (!tokenClient){ console.error('Token client not inited'); return; }
        tokenClient.callback = async function(resp){
            if (resp && resp.error){
                console.error('OAuth error', resp);
                return;
            }
            const token = gapi.client.getToken();
            if (token && token.access_token){
                const expires = token.expires_in ? Math.floor(Date.now()/1000) + parseInt(token.expires_in,10) : 0;
                try{
                    await fetch(wresslaGCal.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: 'action=wressla_gcal_save_token'
                              + '&nonce=' + encodeURIComponent(wresslaGCal.nonce)
                              + '&token=' + encodeURIComponent(token.access_token)
                              + '&expires=' + expires,
                    });
                    const signout = document.getElementById('signout_button');
                    if (signout) signout.style.display = 'inline-block';
                    const authorize = document.getElementById('authorize_button');
                    if (authorize) authorize.innerText = 'Refresh';
                }catch(e){
                    console.error('Token save failed', e);
                }
            }
            try{
                await listUpcomingEvents();
            }catch(e){
                console.warn('List events failed', e);
            }
        };
        if (gapi.client.getToken() === null){
            tokenClient.requestAccessToken({prompt: 'consent'});
        } else {
            tokenClient.requestAccessToken({prompt: ''});
        }
    };

    window.handleSignoutClick = function(){
        const token = gapi.client.getToken();
        if (token && token.access_token){
            try{
                google.accounts.oauth2.revoke(token.access_token);
            }catch(e){}
        }
        gapi.client.setToken(null);
        const authorize = document.getElementById('authorize_button');
        if (authorize) authorize.innerText = 'Authorize';
        const signout = document.getElementById('signout_button');
        if (signout) signout.style.display = 'none';
        const content = document.getElementById('content');
        if (content) content.textContent = '';
    };

    async function listUpcomingEvents(){
        try{
            const resp = await gapi.client.calendar.events.list({
                calendarId: 'primary',
                timeMin: (new Date()).toISOString(),
                showDeleted: false,
                singleEvents: true,
                maxResults: 5,
                orderBy: 'startTime',
            });
            const events = resp && resp.result ? resp.result.items : [];
            const content = document.getElementById('content');
            if (!content) return;
            if (!events || !events.length){
                content.textContent = 'No events found.';
                return;
            }
            let out = 'Events:\n';
            for (const ev of events){
                const when = ev.start.dateTime || ev.start.date || '';
                out += `${ev.summary || '(no title)'} (${when})\n`;
            }
            content.textContent = out;
        }catch(e){
            const code = e.status || (e.result && e.result.error && e.result.error.code);
            if (code === 401){
                alert('Google authorization expired. Please authorize again.');
                handleSignoutClick();
                return;
            }
            throw e;
        }
    }
})();