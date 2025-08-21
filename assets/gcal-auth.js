 (function(){
    const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';
    const SCOPES = 'https://www.googleapis.com/auth/calendar';
    let tokenClient;
    let gapiInited = false;
    let gisInited = false;

    window.gapiLoaded = function(){
        gapi.load('client', initializeGapiClient);
    };
    async function initializeGapiClient(){
        await gapi.client.init({
            apiKey: wresslaGCal.apiKey,
            discoveryDocs: [DISCOVERY_DOC],
        });
        gapiInited = true;
        maybeEnableButtons();
    }
    window.gisLoaded = function(){
        tokenClient = google.accounts.oauth2.initTokenClient({
            client_id: wresslaGCal.clientId,
            scope: SCOPES,
            callback: '',
        });
        gisInited = true;
        maybeEnableButtons();
    };
    function maybeEnableButtons(){
        if (gapiInited && gisInited) {
            const btn = document.getElementById('authorize_button');
            if (btn) btn.removeAttribute('disabled');
        }
    }
    window.handleAuthClick = function(){
        tokenClient.callback = async (resp) => {
            if (resp.error !== undefined) {
                console.error(resp);
                return;
            }
            const signout = document.getElementById('signout_button');
            const authorize = document.getElementById('authorize_button');
            if (signout) signout.style.display = 'inline-block';
            if (authorize) authorize.innerText = 'Refresh';
            const token = gapi.client.getToken();
            if (token) {
                fetch(wresslaGCal.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=wressla_gcal_save_token&nonce='+wresslaGCal.nonce+'&token='+encodeURIComponent(token.access_token)
                });
            }
            await listUpcomingEvents();
        };
        if (gapi.client.getToken() === null) {
            tokenClient.requestAccessToken({prompt:'consent'});
        } else {
            tokenClient.requestAccessToken({prompt:''});
        }
    };
    window.handleSignoutClick = function(){
        const token = gapi.client.getToken();
        if (token !== null) {
            google.accounts.oauth2.revoke(token.access_token);
            gapi.client.setToken('');
            const content = document.getElementById('content');
            if (content) content.innerText = '';
            const authorize = document.getElementById('authorize_button');
            const signout = document.getElementById('signout_button');
            if (authorize) authorize.innerText = 'Authorize';
            if (signout) signout.style.display = 'none';
        }
    };
    async function listUpcomingEvents(){
        let response;
        try {
            response = await gapi.client.calendar.events.list({
                'calendarId': 'primary',
                'timeMin': (new Date()).toISOString(),
                'showDeleted': false,
                'singleEvents': true,
                'maxResults': 10,
                'orderBy': 'startTime',
            });
        } catch (err) {
            const content = document.getElementById('content');
            if (content) content.innerText = err.message;
            return;
        }
        const events = response.result.items;
        const content = document.getElementById('content');
        if (!events || events.length === 0) {
            if (content) content.innerText = 'No events found.';
            return;
        }
        const output = events.reduce((str, event) => `${str}${event.summary} (${event.start.dateTime || event.start.date})\n`, 'Events:\n');
        if (content) content.innerText = output;
    }
})();
