// Calculs automatiques des formulaires RentalPro
const nombre = (v) => parseFloat(String(v ?? '').replace(/\s/g, '').replace(',', '.')) || 0;
const fr = (n, d = 2) => n.toLocaleString('fr-FR', { minimumFractionDigits: d, maximumFractionDigits: d });

document.addEventListener('DOMContentLoaded', () => {
    // Formulaire de location : sous-total, commission, total USD et CDF
    const f = document.getElementById('form-location');
    if (f) {
        const calc = () => {
            const brut = nombre(f.montant_journalier.value) * nombre(f.nombre_jours.value);
            const com = brut * nombre(f.pourcentage.value) / 100;
            const net = brut - com;
            f.querySelector('[data-calc=brut]').textContent = fr(brut) + ' $';
            f.querySelector('[data-calc=commission]').textContent = fr(com) + ' $';
            f.querySelector('[data-calc=total]').textContent = fr(net) + ' $';
            f.querySelector('[data-calc=cdf]').textContent = fr(net * nombre(f.taux.value), 0) + ' FC';
        };
        ['montant_journalier', 'nombre_jours', 'pourcentage', 'taux'].forEach((n) => f[n]?.addEventListener('input', calc));
        calc();
    }

    // Afficher / masquer le mot de passe
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});
