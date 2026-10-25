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

    // Afficher / masquer le mot de passe
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});
