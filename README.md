# Wressla Core

Plugin WordPress do obsługi rezerwacji z potwierdzeniem e‑mail i weryfikacją wolnych terminów.

## Integracja z Google Calendar

Aby wtyczka mogła sprawdzać dostępność terminów w Twoim kalendarzu Google, wykonaj poniższe kroki:

1. Wejdź do [Google Cloud Console](https://console.cloud.google.com/) i utwórz projekt.
2. Włącz **Google Calendar API** i wygeneruj dla projektu **API Key**.
3. Otwórz [Google Calendar](https://calendar.google.com/), przejdź do ustawień wybranego kalendarza i skopiuj pole **ID kalendarza**.
4. W panelu WordPress przejdź do **Wressla → Ustawienia → Kalendarz** i wprowadź wartości w polach **Google Calendar API Key** oraz **ID kalendarza Google**.
5. Zapisz ustawienia.

Po zapisaniu rezerwacje będą porównywane z wpisami w podanym kalendarzu, co uniemożliwi podwójne bukowanie zajętych terminów.

