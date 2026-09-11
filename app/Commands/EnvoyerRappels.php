<?php

namespace App\Commands;

use App\Models\RendezVousModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * En prod, cette commande serait déclenchée par un vrai cron système
 * (ex: `* * * * * php spark rappels:envoyer`). Ici on la lance à la main
 * pour la démo — elle balaie une fenêtre glissante de 24h, donc peu
 * importe la fréquence réelle d'exécution, aucun RDV n'est oublié ni
 * rappelé deux fois (cf. RendezVousModel::aRappeler() + le flag
 * rappel_envoye).
 */
class EnvoyerRappels extends BaseCommand
{
    protected $group = 'QualiDoc';
    protected $name = 'rappels:envoyer';
    protected $description = "Envoie un mail de rappel aux patients dont le rendez-vous confirmé a lieu dans les prochaines 24h.";

    public function run(array $params)
    {
        $model = new RendezVousModel();
        $rdvs = $model->aRappeler();

        if ($rdvs === []) {
            CLI::write('Aucun rappel à envoyer.', 'yellow');

            return EXIT_SUCCESS;
        }

        $envoyes = 0;
        $echecs = 0;

        foreach ($rdvs as $rdv) {
            try {
                $email = service('email');
                $email->setTo($rdv['patient_email']);
                $email->setSubject('Rappel de votre rendez-vous QualiDoc');
                $email->setMessage($this->corps($rdv));

                if (! $email->send()) {
                    throw new \RuntimeException($email->printDebugger(['headers']));
                }

                $model->update($rdv['id'], ['rappel_envoye' => true]);
                $envoyes++;
            } catch (\Throwable $e) {
                $echecs++;
                log_message('error', "Echec envoi rappel RDV #{$rdv['id']} : {$e->getMessage()}");
            }
        }

        CLI::write("{$envoyes} rappel(s) envoyé(s), {$echecs} échec(s).", $echecs > 0 ? 'yellow' : 'green');

        return EXIT_SUCCESS;
    }

    private function corps(array $rdv): string
    {
        $date = date('d/m/Y à H:i', strtotime($rdv['date_heure']));

        return "Bonjour {$rdv['patient_prenom']},\n\n"
            . "Rappel : vous avez rendez-vous le {$date} avec "
            . "{$rdv['medecin_prenom']} {$rdv['medecin_nom']} ({$rdv['specialite_nom']}).\n\n"
            . "À bientôt,\nL'équipe QualiDoc";
    }
}
