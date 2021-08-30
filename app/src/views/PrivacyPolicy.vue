<template>
  <b-container role="main" tag="main">
    <h1 class="mb-4">Datenschutz</h1>
    <b-row>
      <b-col lg="6">
        <p>Diese Datenschutzerklärung gilt für den deutschsprachigen Auftritt von Arch Linux unter
        <router-link :to="{name: 'start'}">archlinux.de</router-link>. Hier erfährst Du welche personenbezogenen
        Daten bei der Interaktion mit <router-link :to="{name: 'start'}">archlinux.de</router-link> anfallen und
        für welchen Zweck sie wie lange gespeichert werden.</p>
        <h2>IP-Adresse</h2>
        <h3>Server-Logs</h3>
        <p>Alle Zugriffe auf diese Seiten werden protokolliert. Hierbei werden folgende Daten erfasst:</p>
        <ul>
          <li>Deine aktuelle IP-Adresse</li>
          <li>Datum und Uhrzeit des Zugriffs</li>
          <li>Anfrage des Browser</li>
          <li>Vom Browser gesendeter Referer</li>
          <li>Vom Browser gesendete Kennung</li>
        </ul>
        <p>Aus diesen Daten werden anonymisierte Statistiken erstellt. Zusätzlich werden sie zur Erkennung und
          Abwehr von Angriffen verwendet. Die Logdaten werden für einen Monat archiviert.</p>
        <h3>Beiträge im Forum und Wiki</h3>
        <p>Veröffentlichst Du Beiträge im <a href="https://forum.archlinux.de/">Forum</a> oder
          <a href="https://wiki.archlinux.de/">Wiki</a> so wird auch hier Deine IP-Adresse protokolliert.
          Dies ist notwendig um mögliche Angriffe abzuwehren und Straftaten zu verfolgen. Diese Daten werden
          zusammen mit den jeweiligen Beiträgen entfernt.</p>
        <h2>Cookies</h2>
        <p>Beim Login werden technisch notwendige Cookies gesetzt, die für die Dauer der Session gültig sind.
          Diese
          werden zudem beim Logout entfernt.</p>
        <p>Darüber hinaus kannst Du beim Login optional ein langfristiges Cookie setzen, um bei künftigen
          Besuchen
          automatisch angemeldet zu werden. Auch dieses Cookie lässt sich expliziet durch einen Logout
          entfernen.</p>
      </b-col>
      <b-col class="pl-lg-5" lg="6">
        <h2>Benutzerkonten</h2>
        <p>Bei der Registrierung im <a href="https://forum.archlinux.de/">Forum</a> wird neben einem
          Benutzernamen
          auch Deine E-Mail-Adresse gespeichert. Alle weiteren Angaben im Profil sind freiwillig und
          öffentlich zugänglich. Diese kannst Du jederzeit unter <em>Profil</em> im
          <a href="https://forum.archlinux.de/">Forum</a> einsehen und ändern.
          Dein im <a href="https://forum.archlinux.de/">Forum</a> angelegtes Benutzer-Konto wird
          zudem für das <a href="https://wiki.archlinux.de/">Wiki</a> verwendet.</p>
        <h2>Löschen des Benutzerkontos</h2>
        <p>Du kannst Dein Konto und die enthaltenen Daten auf Wunsch löschen lassen. Schicke hierzu eine formlose
          Anfrage an die im <router-link :to="{name: 'impressum'}">Impressum</router-link>
          hinterlegte E-Mail-Adresse.
          Alle von Dir verfassten Beiträge im <a href="https://forum.archlinux.de/">Forum</a> oder
          <a href="https://wiki.archlinux.de/">Wiki</a> bleiben hierbei erhalten. Dein Benutzername selbst
          wird weiterhin als Verfasser angezeigt.
        </p>
        <h2>Änderung des Benutzernamens</h2>
        <p>Auf besonderen Wunsch hin kann der Benutzername geändert werden. Diese Anfrage muss jedoch unbedingt
          vor dem Löschen des Benutzerkontos erfolgen. Schicke hierzu eine formlose Anfrage an die im
          <router-link :to="{name: 'impressum'}">Impressum</router-link> hinterlegte E-Mail-Adresse.
        </p>
        <h2>Auskunft über personenbezogene Daten</h2>
        <p>Du hast das Recht Auskunft über die hier gespeicherten personenbezogenen Daten zu erfragen. Nutze
          auch hierzu die im <router-link :to="{name: 'impressum'}">Impressum</router-link> hinterlegte E-Mail-Adresse.
        </p>
        <h2>Kontakt</h2>
        <p>Weitere Kontakt-Möglichkeiten findest Du im <router-link :to="{name: 'impressum'}">Impressum</router-link>.
        </p>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
export default {
  name: 'PrivacyPolicy',
  metaInfo: {
    title: 'Datenschutz',
    meta: [{ vmid: 'robots', name: 'robots', content: 'noindex' }]
  }
}
</script>
