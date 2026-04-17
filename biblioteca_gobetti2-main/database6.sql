-- --------------------------------------------------------

--
-- Struttura della tabella `verifica_email`
--

CREATE TABLE `verifica_email` (
  `id` int(11) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `codice` varchar(20) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `messaggio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `abbinamentiperiodo`
--
ALTER TABLE `abbinamentiperiodo`
  ADD PRIMARY KEY (`IDAbbinamentiPeriodo`),
  ADD KEY `prenotazione_periodo` (`idAziendePeriodo`),
  ADD KEY `fk_idTutorAzi` (`idTutorAzi`),
  ADD KEY `fk_idUtente` (`idUtente`);

--
-- Indici per le tabelle `abilitato`
--
ALTER TABLE `abilitato`
  ADD PRIMARY KEY (`IDAbilitato`),
  ADD KEY `fk_abilitato_profili` (`idProfilo`),
  ADD KEY `fk_abilitato_classiConcorso` (`idClasseConcorso`);

--
-- Indici per le tabelle `ambienti`
--
ALTER TABLE `ambienti`
  ADD PRIMARY KEY (`IDAmbiente`),
  ADD KEY `fk_ambienti_id` (`idTipoMBN`);

--
-- Indici per le tabelle `anniscolastici`
--
ALTER TABLE `anniscolastici`
  ADD PRIMARY KEY (`IDAnnoScolastico`);

--
-- Indici per le tabelle `apparecchiaturesicurezza`
--
ALTER TABLE `apparecchiaturesicurezza`
  ADD PRIMARY KEY (`IDAppaSicurezza`);

--
-- Indici per le tabelle `archivi`
--
ALTER TABLE `archivi`
  ADD PRIMARY KEY (`ARC_ID`);

--
-- Indici per le tabelle `argomenticompetenze`
--
ALTER TABLE `argomenticompetenze`
  ADD PRIMARY KEY (`IDArgomentiCompetenza`),
  ADD KEY `idTitoloCompetenza` (`idTitoloCompetenza`),
  ADD KEY `idTutorAziendale` (`idTutorAziendale`);

--
-- Indici per le tabelle `assemblee`
--
ALTER TABLE `assemblee`
  ADD PRIMARY KEY (`IDAssemblea`),
  ADD KEY `assemblee_IDAnnoScolastico_anniScolastici` (`idAnnoScolastico`);

--
-- Indici per le tabelle `assistenze`
--
ALTER TABLE `assistenze`
  ADD PRIMARY KEY (`IDAssistenza`),
  ADD KEY `idIntervento` (`idTicket`,`idTecnicoCollaudo`),
  ADD KEY `idTecnicoCollaudo` (`idTecnicoCollaudo`),
  ADD KEY `idTicket` (`idTicket`),
  ADD KEY `idTicket_2` (`idTicket`),
  ADD KEY `assistenza_aziendaAssistenza` (`idAziendaAssistenza`);

--
-- Indici per le tabelle `attestatifirmati`
--
ALTER TABLE `attestatifirmati`
  ADD PRIMARY KEY (`IDAttestatoFirmato`),
  ADD KEY `idUtente` (`idUtente`),
  ADD KEY `idCorso` (`idCorso`);

--
-- Indici per le tabelle `attivita`
--
ALTER TABLE `attivita`
  ADD PRIMARY KEY (`IDAttivita`),
  ADD KEY `attivita_IDAmbiente_ambienti` (`idAmbiente`),
  ADD KEY `attivita_IDAssemblea_assemblee` (`idAssemblea`);

--
-- Indici per le tabelle `attivitàopenday`
--
ALTER TABLE `attivitàopenday`
  ADD PRIMARY KEY (`IDAttività`),
  ADD KEY `idAmbiente` (`idAmbiente`),
  ADD KEY `idOpenday` (`idOpenday`);

--
-- Indici per le tabelle `autocertificazione_dvr`
--
ALTER TABLE `autocertificazione_dvr`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_azienda` (`id_azienda`),
  ADD KEY `id_azienda` (`id_azienda`);

--
-- Indici per le tabelle `aziende`
--
ALTER TABLE `aziende`
  ADD PRIMARY KEY (`IDazienda`);

--
-- Indici per le tabelle `aziendeassistenze`
--
ALTER TABLE `aziendeassistenze`
  ADD PRIMARY KEY (`IDazienda`);

--
-- Indici per le tabelle `caratteristiche_ricambi`
--
ALTER TABLE `caratteristiche_ricambi`
  ADD PRIMARY KEY (`IDCaratteristica`);

--
-- Indici per le tabelle `cassettaprontosoccorso`
--
ALTER TABLE `cassettaprontosoccorso`
  ADD PRIMARY KEY (`IDCassettaProntoSoccorso`);

--
-- Indici per le tabelle `cassetteprontosoccorso_prodottiprontosoccorso`
--
ALTER TABLE `cassetteprontosoccorso_prodottiprontosoccorso`
  ADD PRIMARY KEY (`IDCassettaProntoSoccorso_prodottoProntoSoccorso`);

--
-- Indici per le tabelle `classi`
--
ALTER TABLE `classi`
  ADD PRIMARY KEY (`IDClasse`),
  ADD KEY `IDIndirizzo` (`IDIndirizzo`);

--
-- Indici per le tabelle `classiconcorso`
--
ALTER TABLE `classiconcorso`
  ADD PRIMARY KEY (`IDClasseConcorso`);

--
-- Indici per le tabelle `colori`
--
ALTER TABLE `colori`
  ADD PRIMARY KEY (`IDColore`);

--
-- Indici per le tabelle `competenze`
--
ALTER TABLE `competenze`
  ADD PRIMARY KEY (`IDTitoloCompetenza`);

--
-- Indici per le tabelle `componenti_elettronici`
--
ALTER TABLE `componenti_elettronici`
  ADD PRIMARY KEY (`IDComponenteElettronico`);

--
-- Indici per le tabelle `controlliapparecchiaturesicurezza`
--
ALTER TABLE `controlliapparecchiaturesicurezza`
  ADD PRIMARY KEY (`IDControllo`);

--
-- Indici per le tabelle `controllicassette`
--
ALTER TABLE `controllicassette`
  ADD PRIMARY KEY (`IDControllo`);

--
-- Indici per le tabelle `corsi`
--
ALTER TABLE `corsi`
  ADD PRIMARY KEY (`IDCorso`),
  ADD KEY `idTipoCorso` (`idTipoCorso`);

--
-- Indici per le tabelle `corsiutenti`
--
ALTER TABLE `corsiutenti`
  ADD PRIMARY KEY (`IDCorsoUtente`),
  ADD KEY `IDCorso` (`IDCorso`);

--
-- Indici per le tabelle `destinatari`
--
ALTER TABLE `destinatari`
  ADD PRIMARY KEY (`IDDestinatari`);

--
-- Indici per le tabelle `dettagliprenotazioni`
--
ALTER TABLE `dettagliprenotazioni`
  ADD PRIMARY KEY (`IDDettagliPrenotazione`),
  ADD KEY `FK_dettagliPrenotazioni_idStock_gadgetStock` (`idStock`);

--
-- Indici per le tabelle `dispositivi`
--
ALTER TABLE `dispositivi`
  ADD PRIMARY KEY (`IDDispositivo`),
  ADD KEY `idTipologia` (`idTipologia`),
  ADD KEY `idAmbiente` (`idAmbiente`);

--
-- Indici per le tabelle `gadget`
--
ALTER TABLE `gadget`
  ADD PRIMARY KEY (`IDGadget`);

--
-- Indici per le tabelle `gadgetstock`
--
ALTER TABLE `gadgetstock`
  ADD PRIMARY KEY (`IDStock`),
  ADD KEY `FK_gadgetStock_idTipo_tipiGadget` (`idTipo`),
  ADD KEY `FK_gadgetStock_idGadget_gadget` (`idGadget`),
  ADD KEY `FK_gadgetStock_idColore_colori` (`idColore`),
  ADD KEY `FK_gadgetStock_idTaglia_taglie` (`idTaglia`);

--
-- Indici per le tabelle `gadget_prenotazioni`
--
ALTER TABLE `gadget_prenotazioni`
  ADD PRIMARY KEY (`IDPrenotazione`),
  ADD KEY `idUtente` (`idUtente`),
  ADD KEY `idStato` (`idStato`),
  ADD KEY `idDettagliPrenotazione` (`idDettagliPrenotazione`);

--
-- Indici per le tabelle `gestioneiefp`
--
ALTER TABLE `gestioneiefp`
  ADD PRIMARY KEY (`IDGroup`);

--
-- Indici per le tabelle `giorninoassemblea`
--
ALTER TABLE `giorninoassemblea`
  ADD PRIMARY KEY (`IDGiornoNoAssemblea`),
  ADD KEY `giorniNoAssemblea_IDAnnoScolastico_anniScolastici` (`idAnnoScolastico`);

--
-- Indici per le tabelle `gi_cap`
--
ALTER TABLE `gi_cap`
  ADD PRIMARY KEY (`codice_istat`,`cap`);

--
-- Indici per le tabelle `gi_comuni`
--
ALTER TABLE `gi_comuni`
  ADD PRIMARY KEY (`codice_istat`);

--
-- Indici per le tabelle `gi_comuni_cap`
--
ALTER TABLE `gi_comuni_cap`
  ADD PRIMARY KEY (`codice_istat`,`cap`);

--
-- Indici per le tabelle `gi_comuni_validita`
--
ALTER TABLE `gi_comuni_validita`
  ADD PRIMARY KEY (`codice_istat`,`codice_belfiore`,`data_inizio_validita`);

--
-- Indici per le tabelle `gi_nazioni`
--
ALTER TABLE `gi_nazioni`
  ADD PRIMARY KEY (`sigla_nazione`),
  ADD UNIQUE KEY `codice_belfiore` (`codice_belfiore`);

--
-- Indici per le tabelle `gi_province`
--
ALTER TABLE `gi_province`
  ADD PRIMARY KEY (`sigla_provincia`),
  ADD UNIQUE KEY `codice_sovracomunale` (`codice_sovracomunale`);

--
-- Indici per le tabelle `gi_regioni`
--
ALTER TABLE `gi_regioni`
  ADD PRIMARY KEY (`codice_regione`);

--
-- Indici per le tabelle `impostazioni`
--
ALTER TABLE `impostazioni`
  ADD PRIMARY KEY (`IDImpostazione`);

--
-- Indici per le tabelle `indirizzi`
--
ALTER TABLE `indirizzi`
  ADD PRIMARY KEY (`IDIndirizzo`);

--
-- Indici per le tabelle `interventi`
--
ALTER TABLE `interventi`
  ADD PRIMARY KEY (`IDintervento`),
  ADD KEY `idTicket` (`idTecnico`),
  ADD KEY `idAmbiente` (`idAmbiente`,`idDispositivo`),
  ADD KEY `idDispositivo` (`idDispositivo`);

--
-- Indici per le tabelle `linkaltrigadget`
--
ALTER TABLE `linkaltrigadget`
  ADD PRIMARY KEY (`IDLinkGadget`);

--
-- Indici per le tabelle `materie`
--
ALTER TABLE `materie`
  ADD PRIMARY KEY (`IDMateria`);

--
-- Indici per le tabelle `openday`
--
ALTER TABLE `openday`
  ADD PRIMARY KEY (`IDOpenday`),
  ADD KEY `FK_openday_IdIndirizzo_indirizzi` (`IdIndirizzo`);

--
-- Indici per le tabelle `oraritutor`
--
ALTER TABLE `oraritutor`
  ADD PRIMARY KEY (`IDOrario`),
  ADD KEY `orariTutor_ibfk_1` (`IDTutorAziendale`);

--
-- Indici per le tabelle `ore`
--
ALTER TABLE `ore`
  ADD PRIMARY KEY (`IDOra`);

--
-- Indici per le tabelle `pagamenti`
--
ALTER TABLE `pagamenti`
  ADD PRIMARY KEY (`IDPagamento`);

--
-- Indici per le tabelle `partecipantiattivita`
--
ALTER TABLE `partecipantiattivita`
  ADD PRIMARY KEY (`IDPartecipante`),
  ADD KEY `partecipantiAttivita_IDUtente_utenti` (`idUtente`),
  ADD KEY `partecipantiAttivita_IDAttivita_attivita` (`idAttivita`),
  ADD KEY `partecipantiAttivita_IDRuolo_ruoliAttivita` (`idRuolo`);

--
-- Indici per le tabelle `periodi`
--
ALTER TABLE `periodi`
  ADD PRIMARY KEY (`IDPeriodo`),
  ADD KEY `idClasse` (`idClasse`);

--
-- Indici per le tabelle `pezzi_ricambio`
--
ALTER TABLE `pezzi_ricambio`
  ADD PRIMARY KEY (`IDPezziRicambio`);

--
-- Indici per le tabelle `pianilotti`
--
ALTER TABLE `pianilotti`
  ADD PRIMARY KEY (`IDPianoLotto`);

--
-- Indici per le tabelle `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD PRIMARY KEY (`idPrenotazione`),
  ADD KEY `FK_idTipoAmbienti` (`idTipoAmbiente`),
  ADD KEY `FK_idAmbienti` (`idAmbiente`),
  ADD KEY `FK_idOra` (`idOra`);

--
-- Indici per le tabelle `prenotazioniopenday`
--
ALTER TABLE `prenotazioniopenday`
  ADD PRIMARY KEY (`IDprenotazione`),
  ADD KEY `prenotazioniOpenday_IDAttività_attivitàOpenday` (`idAttività`);

--
-- Indici per le tabelle `prenotazioni_periodi`
--
ALTER TABLE `prenotazioni_periodi`
  ADD PRIMARY KEY (`IDPrenotazione`),
  ADD KEY `IDPeriodo` (`IDPeriodo`),
  ADD KEY `IDAzienda` (`IDAzienda`);

--
-- Indici per le tabelle `prodottiobbligatoricassetteprontosoccorso`
--
ALTER TABLE `prodottiobbligatoricassetteprontosoccorso`
  ADD PRIMARY KEY (`IDProdottoObbligatorioCassetteProntoSoccorso`);

--
-- Indici per le tabelle `prodottiprontosoccorso`
--
ALTER TABLE `prodottiprontosoccorso`
  ADD PRIMARY KEY (`IDProdottoProntoSoccorso`);

--
-- Indici per le tabelle `prodottipulizia`
--
ALTER TABLE `prodottipulizia`
  ADD PRIMARY KEY (`IDProdottoPulizia`);

--
-- Indici per le tabelle `profili`
--
ALTER TABLE `profili`
  ADD PRIMARY KEY (`IDProfilo`),
  ADD KEY `fk_profili_utenti` (`idUtente`);

--
-- Indici per le tabelle `referentiaziende`
--
ALTER TABLE `referentiaziende`
  ADD PRIMARY KEY (`IDReferenteAzienda`);

--
-- Indici per le tabelle `richieste`
--
ALTER TABLE `richieste`
  ADD PRIMARY KEY (`IDRichiesta`);

--
-- Indici per le tabelle `rischi`
--
ALTER TABLE `rischi`
  ADD PRIMARY KEY (`IDRischio`),
  ADD KEY `idMateria` (`idMateria`),
  ADD KEY `idIndirizzo` (`idIndirizzo`);

--
-- Indici per le tabelle `ruoli`
--
ALTER TABLE `ruoli`
  ADD PRIMARY KEY (`IDRuolo`);

--
-- Indici per le tabelle `ruoliattivita`
--
ALTER TABLE `ruoliattivita`
  ADD PRIMARY KEY (`IDRuolo`);

--
-- Indici per le tabelle `sede`
--
ALTER TABLE `sede`
  ADD PRIMARY KEY (`idSede`),
  ADD KEY `idAzienda` (`idAzienda`);

--
-- Indici per le tabelle `settoriaziende`
--
ALTER TABLE `settoriaziende`
  ADD PRIMARY KEY (`IDsettore`);

--
-- Indici per le tabelle `sostituzioni`
--
ALTER TABLE `sostituzioni`
  ADD PRIMARY KEY (`IDSost`),
  ADD KEY `fk_sostituzioni_utenti` (`idUtenteAssente`),
  ADD KEY `fk_sostituzioni_utentiSostituto` (`idUtenteSostituto`),
  ADD KEY `fk_sostituzioni_ore` (`idOra`),
  ADD KEY `fk_sostituzioni_classi` (`idClasse`),
  ADD KEY `fk_sostituzioni_ambienti` (`idAmbiente`);

--
-- Indici per le tabelle `sottocorsi`
--
ALTER TABLE `sottocorsi`
  ADD PRIMARY KEY (`IDSottocorso`),
  ADD KEY `idUtente` (`idUtente`),
  ADD KEY `idRischio` (`idRischio`),
  ADD KEY `idTipoLezione` (`idTipoLezione`),
  ADD KEY `idCorso` (`idCorso`);

--
-- Indici per le tabelle `statisticheticket`
--
ALTER TABLE `statisticheticket`
  ADD PRIMARY KEY (`IDStatisticheTicket`),
  ADD KEY `statisticheTicketId` (`idTicket`),
  ADD KEY `statisticheInterventiId` (`idIntervento`);

--
-- Indici per le tabelle `statoordini`
--
ALTER TABLE `statoordini`
  ADD PRIMARY KEY (`IDStato`),
  ADD UNIQUE KEY `idPagamento` (`idPagamento`);

--
-- Indici per le tabelle `studenti`
--
ALTER TABLE `studenti`
  ADD PRIMARY KEY (`IDStudente`),
  ADD KEY `IDUtente` (`IDUtente`),
  ADD KEY `fk_IDClasse` (`IDClasse`);

--
-- Indici per le tabelle `taglie`
--
ALTER TABLE `taglie`
  ADD PRIMARY KEY (`IDTaglia`);

--
-- Indici per le tabelle `template`
--
ALTER TABLE `template`
  ADD PRIMARY KEY (`new_column`);

--
-- Indici per le tabelle `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`IDTicket`),
  ADD KEY `idTipoTicket` (`idTipoTicket`),
  ADD KEY `idUtente` (`idUtenteCreazione`),
  ADD KEY `idDispositivo` (`idDispositivo`),
  ADD KEY `idAmbiente` (`idAmbiente`),
  ADD KEY `idAmbiente_2` (`idAmbiente`);

--
-- Indici per le tabelle `tickets_apparecchiaturesicurezza`
--
ALTER TABLE `tickets_apparecchiaturesicurezza`
  ADD PRIMARY KEY (`IDTicket_apparecchiaturaSicurezza`);

--
-- Indici per le tabelle `tipiambienti`
--
ALTER TABLE `tipiambienti`
  ADD PRIMARY KEY (`IDTipoAmbiente`);

--
-- Indici per le tabelle `tipiapparecchiaturesicurezza`
--
ALTER TABLE `tipiapparecchiaturesicurezza`
  ADD PRIMARY KEY (`IDTipoAppa`);

--
-- Indici per le tabelle `tipiazienda`
--
ALTER TABLE `tipiazienda`
  ADD PRIMARY KEY (`IDTipo`);

--
-- Indici per le tabelle `tipicassetteprontosoccorso`
--
ALTER TABLE `tipicassetteprontosoccorso`
  ADD PRIMARY KEY (`IDTipoCassettaProntoSoccorso`);

--
-- Indici per le tabelle `tipicorsi`
--
ALTER TABLE `tipicorsi`
  ADD PRIMARY KEY (`IDTipoCorso`),
  ADD KEY `fk_tipo_IEFP` (`IdGroup`);

--
-- Indici per le tabelle `tipigadget`
--
ALTER TABLE `tipigadget`
  ADD PRIMARY KEY (`IDTipo`);

--
-- Indici per le tabelle `tipisede`
--
ALTER TABLE `tipisede`
  ADD PRIMARY KEY (`IDSede`);

--
-- Indici per le tabelle `tipitickets`
--
ALTER TABLE `tipitickets`
  ADD PRIMARY KEY (`IDTipoTicket`);

--
-- Indici per le tabelle `tipi_ricambi`
--
ALTER TABLE `tipi_ricambi`
  ADD PRIMARY KEY (`IDTipoRicambio`);

--
-- Indici per le tabelle `tipolezioni`
--
ALTER TABLE `tipolezioni`
  ADD PRIMARY KEY (`IDTipoLezione`);

--
-- Indici per le tabelle `tipolivelli`
--
ALTER TABLE `tipolivelli`
  ADD PRIMARY KEY (`IDTipoAccount`);

--
-- Indici per le tabelle `tipologiealtro`
--
ALTER TABLE `tipologiealtro`
  ADD PRIMARY KEY (`IDTipologiaAltro`);

--
-- Indici per le tabelle `tipologiedispositivi`
--
ALTER TABLE `tipologiedispositivi`
  ADD PRIMARY KEY (`IDTipologia`);

--
-- Indici per le tabelle `tipologie_componenti`
--
ALTER TABLE `tipologie_componenti`
  ADD PRIMARY KEY (`IDTipologiaComponente`);

--
-- Indici per le tabelle `tipoopenday`
--
ALTER TABLE `tipoopenday`
  ADD PRIMARY KEY (`IDTipoOpenday`);

--
-- Indici per le tabelle `tutoraziendali`
--
ALTER TABLE `tutoraziendali`
  ADD PRIMARY KEY (`IDTutorAziendale`),
  ADD KEY `idRuolo` (`idRuolo`),
  ADD KEY `idSede` (`idSede`),
  ADD KEY `idUtente` (`idUtente`);

--
-- Indici per le tabelle `tutoraziendali_titolocompetenze`
--
ALTER TABLE `tutoraziendali_titolocompetenze`
  ADD PRIMARY KEY (`IDTutorAziendaliTitoloCompetenze`),
  ADD KEY `idTutorAziendale` (`idTutorAziendale`);

--
-- Indici per le tabelle `tutorcompetenze`
--
ALTER TABLE `tutorcompetenze`
  ADD PRIMARY KEY (`IDAbbinamento`),
  ADD KEY `idTutorAziendale` (`IDTutorAziendale`);

--
-- Indici per le tabelle `upload_documents`
--
ALTER TABLE `upload_documents`
  ADD PRIMARY KEY (`IDDocumento`),
  ADD KEY `IDAssegn` (`IDAssegn`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`IDUtente`);

--
-- Indici per le tabelle `utentiresponsabilicorsi`
--
ALTER TABLE `utentiresponsabilicorsi`
  ADD PRIMARY KEY (`IDResp`);

--
-- Indici per le tabelle `utentiticketspresaincarico`
--
ALTER TABLE `utentiticketspresaincarico`
  ADD PRIMARY KEY (`IDUtentiTicketsPresaInCarico`),
  ADD KEY `FK_TicketPresoInCarico` (`idTicket`);

--
-- Indici per le tabelle `utenti_tipolivelli`
--
ALTER TABLE `utenti_tipolivelli`
  ADD PRIMARY KEY (`IDUtente_livello`),
  ADD KEY `idUtente` (`idUtente`),
  ADD KEY `idLivello` (`idLivello`);

--
-- Indici per le tabelle `verifica_email`
--
ALTER TABLE `verifica_email`
  ADD PRIMARY KEY (`id`),
  ADD KEY `codice` (`codice`,`hash`),
  ADD KEY `recipient_email` (`recipient_email`),
  ADD KEY `sender_email` (`sender_email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `abbinamentiperiodo`
--
ALTER TABLE `abbinamentiperiodo`
  MODIFY `IDAbbinamentiPeriodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT per la tabella `abilitato`
--
ALTER TABLE `abilitato`
  MODIFY `IDAbilitato` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `ambienti`
--
ALTER TABLE `ambienti`
  MODIFY `IDAmbiente` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID Ambiente PK', AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT per la tabella `anniscolastici`
--
ALTER TABLE `anniscolastici`
  MODIFY `IDAnnoScolastico` smallint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un singolo anno scolastico', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `apparecchiaturesicurezza`
--
ALTER TABLE `apparecchiaturesicurezza`
  MODIFY `IDAppaSicurezza` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK', AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT per la tabella `archivi`
--
ALTER TABLE `archivi`
  MODIFY `ARC_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `argomenticompetenze`
--
ALTER TABLE `argomenticompetenze`
  MODIFY `IDArgomentiCompetenza` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT per la tabella `assemblee`
--
ALTER TABLE `assemblee`
  MODIFY `IDAssemblea` smallint(4) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica una singola assemblea', AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `assistenze`
--
ALTER TABLE `assistenze`
  MODIFY `IDAssistenza` int(7) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT per la tabella `attestatifirmati`
--
ALTER TABLE `attestatifirmati`
  MODIFY `IDAttestatoFirmato` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `attivita`
--
ALTER TABLE `attivita`
  MODIFY `IDAttivita` mediumint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica l''attività specifica', AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT per la tabella `attivitàopenday`
--
ALTER TABLE `attivitàopenday`
  MODIFY `IDAttività` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `autocertificazione_dvr`
--
ALTER TABLE `autocertificazione_dvr`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT per la tabella `aziende`
--
ALTER TABLE `aziende`
  MODIFY `IDazienda` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT per la tabella `aziendeassistenze`
--
ALTER TABLE `aziendeassistenze`
  MODIFY `IDazienda` int(5) NOT NULL AUTO_INCREMENT COMMENT 'ID azienda', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `caratteristiche_ricambi`
--
ALTER TABLE `caratteristiche_ricambi`
  MODIFY `IDCaratteristica` tinyint(3) NOT NULL AUTO_INCREMENT COMMENT 'ID caratteristica', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `cassettaprontosoccorso`
--
ALTER TABLE `cassettaprontosoccorso`
  MODIFY `IDCassettaProntoSoccorso` tinyint(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `cassetteprontosoccorso_prodottiprontosoccorso`
--
ALTER TABLE `cassetteprontosoccorso_prodottiprontosoccorso`
  MODIFY `IDCassettaProntoSoccorso_prodottoProntoSoccorso` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `classi`
--
ALTER TABLE `classi`
  MODIFY `IDClasse` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT per la tabella `classiconcorso`
--
ALTER TABLE `classiconcorso`
  MODIFY `IDClasseConcorso` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT per la tabella `colori`
--
ALTER TABLE `colori`
  MODIFY `IDColore` smallint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `competenze`
--
ALTER TABLE `competenze`
  MODIFY `IDTitoloCompetenza` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `componenti_elettronici`
--
ALTER TABLE `componenti_elettronici`
  MODIFY `IDComponenteElettronico` int(3) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un componente elettronico', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `controlliapparecchiaturesicurezza`
--
ALTER TABLE `controlliapparecchiaturesicurezza`
  MODIFY `IDControllo` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `controllicassette`
--
ALTER TABLE `controllicassette`
  MODIFY `IDControllo` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `corsi`
--
ALTER TABLE `corsi`
  MODIFY `IDCorso` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT per la tabella `corsiutenti`
--
ALTER TABLE `corsiutenti`
  MODIFY `IDCorsoUtente` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT per la tabella `destinatari`
--
ALTER TABLE `destinatari`
  MODIFY `IDDestinatari` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `dettagliprenotazioni`
--
ALTER TABLE `dettagliprenotazioni`
  MODIFY `IDDettagliPrenotazione` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `dispositivi`
--
ALTER TABLE `dispositivi`
  MODIFY `IDDispositivo` int(4) NOT NULL AUTO_INCREMENT COMMENT 'ID dispositivo', AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT per la tabella `gadget`
--
ALTER TABLE `gadget`
  MODIFY `IDGadget` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `gadgetstock`
--
ALTER TABLE `gadgetstock`
  MODIFY `IDStock` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `gadget_prenotazioni`
--
ALTER TABLE `gadget_prenotazioni`
  MODIFY `IDPrenotazione` smallint(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `gestioneiefp`
--
ALTER TABLE `gestioneiefp`
  MODIFY `IDGroup` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `giorninoassemblea`
--
ALTER TABLE `giorninoassemblea`
  MODIFY `IDGiornoNoAssemblea` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un singolo giorno in cui non è possibile programmare un''assemblea d''istituto', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `impostazioni`
--
ALTER TABLE `impostazioni`
  MODIFY `IDImpostazione` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'PK', AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT per la tabella `indirizzi`
--
ALTER TABLE `indirizzi`
  MODIFY `IDIndirizzo` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `interventi`
--
ALTER TABLE `interventi`
  MODIFY `IDintervento` int(7) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `linkaltrigadget`
--
ALTER TABLE `linkaltrigadget`
  MODIFY `IDLinkGadget` smallint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `materie`
--
ALTER TABLE `materie`
  MODIFY `IDMateria` int(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `openday`
--
ALTER TABLE `openday`
  MODIFY `IDOpenday` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `oraritutor`
--
ALTER TABLE `oraritutor`
  MODIFY `IDOrario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `ore`
--
ALTER TABLE `ore`
  MODIFY `IDOra` tinyint(2) NOT NULL AUTO_INCREMENT COMMENT 'PK', AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `pagamenti`
--
ALTER TABLE `pagamenti`
  MODIFY `IDPagamento` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `partecipantiattivita`
--
ALTER TABLE `partecipantiattivita`
  MODIFY `IDPartecipante` bigint(50) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un singolo partecipante all''attività. Può essere uno studente, la dirigente, un tecnico, etc... non può essere SOLO genitore. Devono essere previsti almeno (numero di utenti * numero di attività) partecipanti', AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT per la tabella `periodi`
--
ALTER TABLE `periodi`
  MODIFY `IDPeriodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT per la tabella `pezzi_ricambio`
--
ALTER TABLE `pezzi_ricambio`
  MODIFY `IDPezziRicambio` int(4) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un pezzo di ricambio', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `pianilotti`
--
ALTER TABLE `pianilotti`
  MODIFY `IDPianoLotto` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  MODIFY `idPrenotazione` int(10) NOT NULL AUTO_INCREMENT COMMENT 'Id prenotazione', AUTO_INCREMENT=553;

--
-- AUTO_INCREMENT per la tabella `prenotazioniopenday`
--
ALTER TABLE `prenotazioniopenday`
  MODIFY `IDprenotazione` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `prenotazioni_periodi`
--
ALTER TABLE `prenotazioni_periodi`
  MODIFY `IDPrenotazione` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT per la tabella `prodottiobbligatoricassetteprontosoccorso`
--
ALTER TABLE `prodottiobbligatoricassetteprontosoccorso`
  MODIFY `IDProdottoObbligatorioCassetteProntoSoccorso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT per la tabella `prodottiprontosoccorso`
--
ALTER TABLE `prodottiprontosoccorso`
  MODIFY `IDProdottoProntoSoccorso` tinyint(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `prodottipulizia`
--
ALTER TABLE `prodottipulizia`
  MODIFY `IDProdottoPulizia` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `profili`
--
ALTER TABLE `profili`
  MODIFY `IDProfilo` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=315;

--
-- AUTO_INCREMENT per la tabella `referentiaziende`
--
ALTER TABLE `referentiaziende`
  MODIFY `IDReferenteAzienda` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT per la tabella `richieste`
--
ALTER TABLE `richieste`
  MODIFY `IDRichiesta` int(4) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un ordine/richiesta', AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `rischi`
--
ALTER TABLE `rischi`
  MODIFY `IDRischio` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ruoli`
--
ALTER TABLE `ruoli`
  MODIFY `IDRuolo` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT per la tabella `ruoliattivita`
--
ALTER TABLE `ruoliattivita`
  MODIFY `IDRuolo` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Specifica il codice del ruolo che svolge il partecipante', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `sede`
--
ALTER TABLE `sede`
  MODIFY `idSede` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=640;

--
-- AUTO_INCREMENT per la tabella `settoriaziende`
--
ALTER TABLE `settoriaziende`
  MODIFY `IDsettore` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `sostituzioni`
--
ALTER TABLE `sostituzioni`
  MODIFY `IDSost` int(5) NOT NULL AUTO_INCREMENT COMMENT 'PK';

--
-- AUTO_INCREMENT per la tabella `sottocorsi`
--
ALTER TABLE `sottocorsi`
  MODIFY `IDSottocorso` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `statisticheticket`
--
ALTER TABLE `statisticheticket`
  MODIFY `IDStatisticheTicket` int(7) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT per la tabella `statoordini`
--
ALTER TABLE `statoordini`
  MODIFY `IDStato` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `studenti`
--
ALTER TABLE `studenti`
  MODIFY `IDStudente` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1394;

--
-- AUTO_INCREMENT per la tabella `taglie`
--
ALTER TABLE `taglie`
  MODIFY `IDTaglia` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `tickets`
--
ALTER TABLE `tickets`
  MODIFY `IDTicket` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT per la tabella `tickets_apparecchiaturesicurezza`
--
ALTER TABLE `tickets_apparecchiaturesicurezza`
  MODIFY `IDTicket_apparecchiaturaSicurezza` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `tipiambienti`
--
ALTER TABLE `tipiambienti`
  MODIFY `IDTipoAmbiente` tinyint(2) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `tipiapparecchiaturesicurezza`
--
ALTER TABLE `tipiapparecchiaturesicurezza`
  MODIFY `IDTipoAppa` tinyint(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `tipiazienda`
--
ALTER TABLE `tipiazienda`
  MODIFY `IDTipo` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT per la tabella `tipicassetteprontosoccorso`
--
ALTER TABLE `tipicassetteprontosoccorso`
  MODIFY `IDTipoCassettaProntoSoccorso` tinyint(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `tipicorsi`
--
ALTER TABLE `tipicorsi`
  MODIFY `IDTipoCorso` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT per la tabella `tipigadget`
--
ALTER TABLE `tipigadget`
  MODIFY `IDTipo` smallint(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `tipisede`
--
ALTER TABLE `tipisede`
  MODIFY `IDSede` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `tipitickets`
--
ALTER TABLE `tipitickets`
  MODIFY `IDTipoTicket` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT per la tabella `tipi_ricambi`
--
ALTER TABLE `tipi_ricambi`
  MODIFY `IDTipoRicambio` tinyint(3) NOT NULL AUTO_INCREMENT COMMENT 'ID tipo elettrico', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `tipolezioni`
--
ALTER TABLE `tipolezioni`
  MODIFY `IDTipoLezione` int(2) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `tipolivelli`
--
ALTER TABLE `tipolivelli`
  MODIFY `IDTipoAccount` tinyint(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT per la tabella `tipologiealtro`
--
ALTER TABLE `tipologiealtro`
  MODIFY `IDTipologiaAltro` tinyint(2) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un tipo', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `tipologiedispositivi`
--
ALTER TABLE `tipologiedispositivi`
  MODIFY `IDTipologia` tinyint(2) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un tipo', AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `tipologie_componenti`
--
ALTER TABLE `tipologie_componenti`
  MODIFY `IDTipologiaComponente` tinyint(2) NOT NULL AUTO_INCREMENT COMMENT 'Id che identifica un tipo', AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT per la tabella `tipoopenday`
--
ALTER TABLE `tipoopenday`
  MODIFY `IDTipoOpenday` int(2) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `tutoraziendali`
--
ALTER TABLE `tutoraziendali`
  MODIFY `IDTutorAziendale` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=370;

--
-- AUTO_INCREMENT per la tabella `tutoraziendali_titolocompetenze`
--
ALTER TABLE `tutoraziendali_titolocompetenze`
  MODIFY `IDTutorAziendaliTitoloCompetenze` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `upload_documents`
--
ALTER TABLE `upload_documents`
  MODIFY `IDDocumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `IDUtente` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2397;

--
-- AUTO_INCREMENT per la tabella `utentiresponsabilicorsi`
--
ALTER TABLE `utentiresponsabilicorsi`
  MODIFY `IDResp` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `utentiticketspresaincarico`
--
ALTER TABLE `utentiticketspresaincarico`
  MODIFY `IDUtentiTicketsPresaInCarico` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT per la tabella `utenti_tipolivelli`
--
ALTER TABLE `utenti_tipolivelli`
  MODIFY `IDUtente_livello` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2542;

--
-- AUTO_INCREMENT per la tabella `verifica_email`
--
ALTER TABLE `verifica_email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `abbinamentiperiodo`
--
ALTER TABLE `abbinamentiperiodo`
  ADD CONSTRAINT `fk_idTutorAzi` FOREIGN KEY (`idTutorAzi`) REFERENCES `tutoraziendali` (`IDTutorAziendale`),
  ADD CONSTRAINT `fk_idUtente` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `prenotazione_periodo` FOREIGN KEY (`idAziendePeriodo`) REFERENCES `prenotazioni_periodi` (`IDPrenotazione`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `abilitato`
--
ALTER TABLE `abilitato`
  ADD CONSTRAINT `fk_abilitato_classiConcorso` FOREIGN KEY (`idClasseConcorso`) REFERENCES `classiconcorso` (`IDClasseConcorso`),
  ADD CONSTRAINT `fk_abilitato_profili` FOREIGN KEY (`idProfilo`) REFERENCES `profili` (`IDProfilo`);

--
-- Limiti per la tabella `ambienti`
--
ALTER TABLE `ambienti`
  ADD CONSTRAINT `fk_ambienti_id` FOREIGN KEY (`idTipoMBN`) REFERENCES `tipiambienti` (`IDTipoAmbiente`);

--
-- Limiti per la tabella `argomenticompetenze`
--
ALTER TABLE `argomenticompetenze`
  ADD CONSTRAINT `argomentiCompetenze_ibfk_1` FOREIGN KEY (`idTitoloCompetenza`) REFERENCES `competenze` (`IDTitoloCompetenza`),
  ADD CONSTRAINT `argomentiCompetenze_ibfk_2` FOREIGN KEY (`idTutorAziendale`) REFERENCES `tutoraziendali` (`IDTutorAziendale`);

--
-- Limiti per la tabella `assistenze`
--
ALTER TABLE `assistenze`
  ADD CONSTRAINT `assistenza_aziendaAssistenza` FOREIGN KEY (`idAziendaAssistenza`) REFERENCES `aziendeassistenze` (`IDazienda`),
  ADD CONSTRAINT `assistenze_ibfk_2` FOREIGN KEY (`idTecnicoCollaudo`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `fk_assistenza_ticket` FOREIGN KEY (`idTicket`) REFERENCES `tickets` (`IDTicket`);

--
-- Limiti per la tabella `attestatifirmati`
--
ALTER TABLE `attestatifirmati`
  ADD CONSTRAINT `attestatifirmati_ibfk_1` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attestatifirmati_ibfk_2` FOREIGN KEY (`idCorso`) REFERENCES `corsi` (`IDCorso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `attivita`
--
ALTER TABLE `attivita`
  ADD CONSTRAINT `attivita_IDAmbiente_ambienti` FOREIGN KEY (`idAmbiente`) REFERENCES `ambienti` (`IDAmbiente`),
  ADD CONSTRAINT `attivita_IDAssemblea_assemblee` FOREIGN KEY (`idAssemblea`) REFERENCES `assemblee` (`IDAssemblea`);

--
-- Limiti per la tabella `autocertificazione_dvr`
--
ALTER TABLE `autocertificazione_dvr`
  ADD CONSTRAINT `fk_auto_dvr_azi` FOREIGN KEY (`id_azienda`) REFERENCES `aziende` (`IDazienda`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `classi`
--
ALTER TABLE `classi`
  ADD CONSTRAINT `classi_ibfk_1` FOREIGN KEY (`IDIndirizzo`) REFERENCES `indirizzi` (`IDIndirizzo`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `corsi`
--
ALTER TABLE `corsi`
  ADD CONSTRAINT `corsi_ibfk_1` FOREIGN KEY (`idTipoCorso`) REFERENCES `tipicorsi` (`IDTipoCorso`);

--
-- Limiti per la tabella `corsiutenti`
--
ALTER TABLE `corsiutenti`
  ADD CONSTRAINT `FK_corsi` FOREIGN KEY (`IDCorso`) REFERENCES `corsi` (`IDCorso`);

--
-- Limiti per la tabella `dettagliprenotazioni`
--
ALTER TABLE `dettagliprenotazioni`
  ADD CONSTRAINT `FK_dettagliPrenotazioni_idStock_gadgetStock` FOREIGN KEY (`idStock`) REFERENCES `gadgetstock` (`IDStock`);

--
-- Limiti per la tabella `dispositivi`
--
ALTER TABLE `dispositivi`
  ADD CONSTRAINT `dispositivi_ibfk_1` FOREIGN KEY (`idTipologia`) REFERENCES `tipologiedispositivi` (`IDTipologia`),
  ADD CONSTRAINT `dispositivi_ibfk_2` FOREIGN KEY (`idAmbiente`) REFERENCES `ambienti` (`IDAmbiente`);

--
-- Limiti per la tabella `gadgetstock`
--
ALTER TABLE `gadgetstock`
  ADD CONSTRAINT `FK_gadgetStock_idColore_colori` FOREIGN KEY (`idColore`) REFERENCES `colori` (`IDColore`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_gadgetStock_idGadget_gadget` FOREIGN KEY (`idGadget`) REFERENCES `gadget` (`IDGadget`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_gadgetStock_idTaglia_taglie` FOREIGN KEY (`idTaglia`) REFERENCES `taglie` (`IDTaglia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_gadgetStock_idTipo_tipiGadget` FOREIGN KEY (`idTipo`) REFERENCES `tipigadget` (`IDTipo`);

--
-- Limiti per la tabella `gadget_prenotazioni`
--
ALTER TABLE `gadget_prenotazioni`
  ADD CONSTRAINT `FK_gadget_prenotazioni_idStato_statoOrdini` FOREIGN KEY (`idStato`) REFERENCES `statoordini` (`IDStato`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_gadget_prenotazioni_idUtente_utenti` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `FK_gadgetprenotazioni_idDettagliPrenotazione_dettagliPrenotazion` FOREIGN KEY (`idDettagliPrenotazione`) REFERENCES `dettagliprenotazioni` (`IDDettagliPrenotazione`);

--
-- Limiti per la tabella `giorninoassemblea`
--
ALTER TABLE `giorninoassemblea`
  ADD CONSTRAINT `giorniNoAssemblea_IDAnnoScolastico_anniScolastici` FOREIGN KEY (`idAnnoScolastico`) REFERENCES `anniscolastici` (`IDAnnoScolastico`);

--
-- Limiti per la tabella `interventi`
--
ALTER TABLE `interventi`
  ADD CONSTRAINT `interventi_ibfk_2` FOREIGN KEY (`idTecnico`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `interventi_ibfk_3` FOREIGN KEY (`idDispositivo`) REFERENCES `dispositivi` (`IDDispositivo`);

--
-- Limiti per la tabella `openday`
--
ALTER TABLE `openday`
  ADD CONSTRAINT `FK_openday_IdIndirizzo_indirizzi` FOREIGN KEY (`IdIndirizzo`) REFERENCES `indirizzi` (`IDIndirizzo`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `oraritutor`
--
ALTER TABLE `oraritutor`
  ADD CONSTRAINT `orariTutor_ibfk_1` FOREIGN KEY (`IDTutorAziendale`) REFERENCES `tutoraziendali` (`IDTutorAziendale`);

--
-- Limiti per la tabella `partecipantiattivita`
--
ALTER TABLE `partecipantiattivita`
  ADD CONSTRAINT `partecipantiAttivita_IDAttivita_attivita` FOREIGN KEY (`idAttivita`) REFERENCES `attivita` (`IDAttivita`),
  ADD CONSTRAINT `partecipantiAttivita_IDRuolo_ruoliAttivita` FOREIGN KEY (`idRuolo`) REFERENCES `ruoliattivita` (`IDRuolo`),
  ADD CONSTRAINT `partecipantiAttivita_IDUtente_utenti` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`);

--
-- Limiti per la tabella `prenotazioni`
--
ALTER TABLE `prenotazioni`
  ADD CONSTRAINT `FK_idAmbienteDispositivi` FOREIGN KEY (`idAmbiente`) REFERENCES `ambienti` (`IDAmbiente`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_idAmbienti` FOREIGN KEY (`idAmbiente`) REFERENCES `ambienti` (`IDAmbiente`),
  ADD CONSTRAINT `FK_idOra` FOREIGN KEY (`idOra`) REFERENCES `ore` (`IDOra`),
  ADD CONSTRAINT `FK_idTipoAmbienti` FOREIGN KEY (`idTipoAmbiente`) REFERENCES `ambienti` (`IDAmbiente`);

--
-- Limiti per la tabella `profili`
--
ALTER TABLE `profili`
  ADD CONSTRAINT `fk_profili_utenti` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`);

--
-- Limiti per la tabella `rischi`
--
ALTER TABLE `rischi`
  ADD CONSTRAINT `rischi_ibfk_1` FOREIGN KEY (`idMateria`) REFERENCES `materie` (`IDMateria`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `rischi_ibfk_2` FOREIGN KEY (`idIndirizzo`) REFERENCES `indirizzi` (`IDIndirizzo`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limiti per la tabella `sede`
--
ALTER TABLE `sede`
  ADD CONSTRAINT `Sede_ibfk_1` FOREIGN KEY (`idAzienda`) REFERENCES `aziende` (`IDazienda`);

--
-- Limiti per la tabella `sostituzioni`
--
ALTER TABLE `sostituzioni`
  ADD CONSTRAINT `fk_sostituzioni_ambienti` FOREIGN KEY (`idAmbiente`) REFERENCES `ambienti` (`IDAmbiente`),
  ADD CONSTRAINT `fk_sostituzioni_classi` FOREIGN KEY (`idClasse`) REFERENCES `classi` (`IDClasse`),
  ADD CONSTRAINT `fk_sostituzioni_ore` FOREIGN KEY (`idOra`) REFERENCES `ore` (`IDOra`),
  ADD CONSTRAINT `fk_sostituzioni_utenti` FOREIGN KEY (`idUtenteAssente`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `fk_sostituzioni_utentiSostituto` FOREIGN KEY (`idUtenteSostituto`) REFERENCES `utenti` (`IDUtente`);

--
-- Limiti per la tabella `sottocorsi`
--
ALTER TABLE `sottocorsi`
  ADD CONSTRAINT `sottocorsi_ibfk_1` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sottocorsi_ibfk_2` FOREIGN KEY (`idRischio`) REFERENCES `rischi` (`IDRischio`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sottocorsi_ibfk_3` FOREIGN KEY (`idTipoLezione`) REFERENCES `tipolezioni` (`IDTipoLezione`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sottocorsi_ibfk_4` FOREIGN KEY (`idCorso`) REFERENCES `corsi` (`IDCorso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `statoordini`
--
ALTER TABLE `statoordini`
  ADD CONSTRAINT `FK_statoOrdini_idPagamento_pagamenti` FOREIGN KEY (`idPagamento`) REFERENCES `pagamenti` (`IDPagamento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `studenti`
--
ALTER TABLE `studenti`
  ADD CONSTRAINT `fk_IDClasse` FOREIGN KEY (`IDClasse`) REFERENCES `classi` (`IDClasse`),
  ADD CONSTRAINT `studenti_ibfk_1` FOREIGN KEY (`IDUtente`) REFERENCES `utenti` (`IDUtente`);

--
-- Limiti per la tabella `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`idUtenteCreazione`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`idDispositivo`) REFERENCES `dispositivi` (`IDDispositivo`),
  ADD CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`idTipoTicket`) REFERENCES `tipitickets` (`IDTipoTicket`);

--
-- Limiti per la tabella `tipicorsi`
--
ALTER TABLE `tipicorsi`
  ADD CONSTRAINT `fk_tipo_IEFP` FOREIGN KEY (`IdGroup`) REFERENCES `gestioneiefp` (`IDGroup`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `tutoraziendali`
--
ALTER TABLE `tutoraziendali`
  ADD CONSTRAINT `tutorAziendali_ibfk_1` FOREIGN KEY (`idRuolo`) REFERENCES `ruoli` (`IDRuolo`),
  ADD CONSTRAINT `tutorAziendali_ibfk_2` FOREIGN KEY (`idSede`) REFERENCES `sede` (`idSede`);

--
-- Limiti per la tabella `tutoraziendali_titolocompetenze`
--
ALTER TABLE `tutoraziendali_titolocompetenze`
  ADD CONSTRAINT `tutorAziendali_titoloCompetenze_ibfk_1` FOREIGN KEY (`idTutorAziendale`) REFERENCES `tutoraziendali` (`IDTutorAziendale`);

--
-- Limiti per la tabella `upload_documents`
--
ALTER TABLE `upload_documents`
  ADD CONSTRAINT `upload_documents_ibfk_1` FOREIGN KEY (`IDAssegn`) REFERENCES `abbinamentiperiodo` (`IDAbbinamentiPeriodo`),
  ADD CONSTRAINT `upload_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `utenti` (`IDUtente`);

--
-- Limiti per la tabella `utentiticketspresaincarico`
--
ALTER TABLE `utentiticketspresaincarico`
  ADD CONSTRAINT `FK_TicketPresoInCarico` FOREIGN KEY (`idTicket`) REFERENCES `tickets` (`IDTicket`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `utenti_tipolivelli`
--
ALTER TABLE `utenti_tipolivelli`
  ADD CONSTRAINT `utenti_tipoLivelli_ibfk_1` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`IDUtente`),
  ADD CONSTRAINT `utenti_tipoLivelli_ibfk_2` FOREIGN KEY (`idLivello`) REFERENCES `tipolivelli` (`IDTipoAccount`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;