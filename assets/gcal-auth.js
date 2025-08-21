(function(){
    const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';
    const SCOPES = 'https://www.googleapis.com/auth/calendar';
    let tokenClient;
    let gapiInited = false;
    let gisInited = false;

    function gapiLoaded(){
        gapi.load('client', initializeGapiClient);
    }
    async function initializeGapiClient(){
        await gapi.client.init({
            apiKey: wresslaGCal.apiKey,
            discoveryDocs: [DISCOVERY_DOC],
        });
        gapiInited = true;
        maybeEnableButtons();
    }
    function gisLoaded(){
        tokenClient = google.accounts.oauth2.initTokenClient({
            client_id: wresslaGCal.clientId,
            scope: SCOPES,
            callback: '',
        });
        gisInited = true;
        maybeEnableButtons();
    }
    function maybeEnableButtons(){
        if (gapiInited && gisInited) {
            const btn = document.getElementById('authorize_button');
            if (btn) btn.style.visibility = 'visible';
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
            if (signout) signout.style.visibility = 'visible';
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
    }
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
            if (signout) signout.style.visibility = 'hidden';
        }
    }
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
    document.addEventListener('DOMContentLoaded', () => {
        const s1 = document.createElement('script');
        s1.src = 'https://apis.google.com/js/api.js';
        s1.async = true; s1.defer = true; s1.onload = gapiLoaded;
        document.body.appendChild(s1);
        const s2 = document.createElement('script');
        s2.src = 'https://accounts.google.com/gsi/client';
        s2.async = true; s2.defer = true; s2.onload = gisLoaded;
        document.body.appendChild(s2);
    });
})();
