// Calculs automatiques des formulaires RentalPro
const nombre = (v) => parseFloat(String(v ?? '').replace(/\s/g, '').replace(',', '.')) || 0;
const fr = (n, d = 2) => n.toLocaleString('fr-FR', { minimumFractionDigits: d, maximumFractionDigits: d });

document.addEventListener('DOMContentLoaded', () => {
    // Formulaires de location (ajout et modification) : sous-total, commission, total USD et CDF, retour prévu
    const clients = document.getElementById('liste-clients');
    document.querySelectorAll('form[id^="form-location"]').forEach((f) => {
        const champ = (nom) => f.elements.namedItem(nom);
        const afficher = (cle, texte) => { const el = f.querySelector(`[data-calc=${cle}]`); if (el) el.textContent = texte; };
        const calc = () => {
            const jours = nombre(champ('nombre_jours')?.value);
            const brut = nombre(champ('montant_journalier')?.value) * jours;
            const com = brut * nombre(champ('pourcentage_commission')?.value) / 100;
            const net = brut - com;
            afficher('brut', fr(brut) + ' $');
            afficher('commission', fr(com) + ' $');
            afficher('total', fr(net) + ' $');
            afficher('cdf', fr(net * nombre(champ('taux_change')?.value), 0) + ' FC');
            const depart = champ('date_depart')?.value;
            if (depart && jours > 0) {
                const fin = new Date(depart + 'T00:00:00');
                fin.setDate(fin.getDate() + jours);
                afficher('fin', fin.toLocaleDateString('fr-FR'));
            } else {
                afficher('fin', '—');
            }
        };
        ['montant_journalier', 'nombre_jours', 'pourcentage_commission', 'taux_change', 'date_depart']
            .forEach((n) => champ(n)?.addEventListener('input', calc));

        // Choix d'un véhicule : propose son tarif journalier
        champ('vehicule_id')?.addEventListener('change', (e) => {
            const tarif = e.target.selectedOptions[0]?.dataset.tarif;
            if (tarif && nombre(champ('montant_journalier').value) === 0) {
                champ('montant_journalier').value = fr(nombre(tarif));
                calc();
            }
        });

        // Client déjà connu : remplit téléphone et adresse
        champ('client_nom')?.addEventListener('change', (e) => {
            const option = clients && [...clients.options].find((o) => o.value === e.target.value);
            if (!option) return;
            if (!champ('client_telephone').value) champ('client_telephone').value = option.dataset.telephone || '';
            if (!champ('client_adresse').value) champ('client_adresse').value = option.dataset.adresse || '';
        });

        calc();
    });

    // Formulaires de dépense et d'autre revenu : source de l'argent, conversion USD ↔ CDF, reste disponible
    document.querySelectorAll('form[id^="form-depense"], form[id^="form-revenu"]').forEach((f) => {
        const champ = (nom) => f.elements.namedItem(nom);
        const zone = f.querySelector('[data-conversion]');
        const blocRevenu = f.querySelector('[data-source-revenu]');

        const sourceChoisie = () => f.querySelector('input[name="source"]:checked')?.value ?? champ('source')?.value;

        const maj = () => {
            // Choix du revenu visible seulement si « Un autre revenu »
            if (blocRevenu) blocRevenu.classList.toggle('hidden', sourceChoisie() !== 'revenu');

            const montant = nombre(champ('montant_saisi')?.value);
            const taux = nombre(champ('taux_change')?.value) || 1;
            const devise = champ('devise')?.value;
            if (!zone) return;
            if (montant <= 0) { zone.textContent = '—'; zone.classList.remove('bg-red-50', 'text-red-800'); return; }

            const usd = devise === 'CDF' ? montant / taux : montant;
            const cdf = devise === 'CDF' ? montant : montant * taux;
            let texte = `= ${fr(usd)} $ · ${fr(cdf, 0)} FC`;

            // Dépense sur un autre revenu : comparer au reste
            const option = sourceChoisie() === 'revenu' ? champ('autre_revenu_id')?.selectedOptions?.[0] : null;
            const fixe = f.querySelector('[data-reste-fixe]')?.dataset.resteFixe;
            const reste = option?.dataset?.reste !== undefined ? nombre(option.dataset.reste) : (fixe !== undefined ? nombre(fixe) : null);
            const depasse = reste !== null && usd > reste + 0.001;
            if (reste !== null) texte += depasse ? ` — dépasse le reste du revenu (${fr(reste)} $)` : ` — il restera ${fr(reste - usd)} $ sur ce revenu`;

            zone.textContent = texte;
            zone.classList.toggle('bg-red-50', depasse);
            zone.classList.toggle('text-red-800', depasse);
        };

        f.addEventListener('input', maj);
        f.addEventListener('change', maj);
        maj();
    });

    // Bloc « Avec quel argent ? » des paiements (avance, salaire, crédit) : choix du revenu et reste disponible
    document.querySelectorAll('[data-source-argent]').forEach((bloc) => {
        const f = bloc.closest('form');
        const choixRevenu = bloc.querySelector('[data-source-choix-revenu]');
        const alerte = bloc.querySelector('[data-source-alerte]');
        const maj = () => {
            const source = f.querySelector('input[name="source"]:checked')?.value;
            choixRevenu.classList.toggle('hidden', source !== 'revenu');
            const option = source === 'revenu' ? f.elements.namedItem('autre_revenu_id')?.selectedOptions?.[0] : null;
            const montant = bloc.dataset.montant !== undefined ? nombre(bloc.dataset.montant) + nombre(f.elements.namedItem('primes')?.value) // montant fixe hors primes + primes saisies
                : nombre(f.elements.namedItem('montant')?.value);
            if (!option?.dataset?.reste || montant <= 0) { alerte.classList.add('hidden'); return; }
            const reste = nombre(option.dataset.reste);
            const ok = montant <= reste + 0.001;
            alerte.textContent = ok ? `Il restera ${fr(reste - montant)} $ sur ce revenu.` : `Pas assez : il ne reste que ${fr(reste)} $ sur ce revenu pour ${fr(montant)} $.`;
            alerte.className = `rounded-lg px-3 py-2 text-xs ${ok ? 'bg-accent-50 text-accent-800' : 'bg-red-50 text-red-800'}`;
        };
        f.addEventListener('input', maj);
        f.addEventListener('change', maj);
        maj();
    });

    // Afficher / masquer le mot de passe
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});
