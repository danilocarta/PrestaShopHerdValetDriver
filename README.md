# Driver per Herd Laravel e Framework PrestaShop

Se sei uno sviluppatore PrestaShop e Laravel/Symfony e utilizzi l'ambiente di sviluppo di Herd Laravel, saprai che non avendo Apache a disposizione le direttive dei file .htaccess vengono spesso ignorate da NGINX la soluzione è questo Driver che simula i redirect di Apache

### Passaggi di Installazione:

1. **Scarica il file `PrestaShopValetDriver.php`**
2. **Accedi alla cartella dei Driver:**
    - Vai alla directory `~/Library/Application Support/Herd/config/valet/Drivers`.
    - Puoi farlo velocemente usando il terminale con:
``` bash
     cd ~/Library/Application\ Support/Herd/config/valet/Drivers
```
1. **Copia il file `PrestaShopValetDriver.php`:**
    - Sposta o copia il file all'interno di questa directory.
    - Se non esiste creala

2. **Riavvia il servizio Herd o il tuo ambiente Valet:**
    - Per assicurarti che il nuovo driver venga riconosciuto, riavvia Herd o il servizio Valet utilizzando il comando:
``` bash
     valet restart
```
### Scopo del Driver:
Il driver è progettato specificamente per identificare e servire progetti PrestaShop mentre utilizzi Laravel Valet. Determina automaticamente se una directory è un progetto PrestaShop e gestisce correttamente le richieste statiche (come immagini o altri file statici) e dinamiche.
Dopo che il driver sarà stato installato e configurato correttamente, Herd Laravel sarà in grado di eseguire un sito PrestaShop con supporto nativo.
