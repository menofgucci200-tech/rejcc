#!/usr/bin/env bash
# =============================================================================
#  REJCC : mise en production en une commande
#
#    ./deploy.sh                    met en ligne la branche « main »
#    ./deploy.sh --verifier         contrôle tout, sans rien modifier
#    ./deploy.sh --aide             toutes les options
#
#  Le script fonctionne de deux façons :
#   - sur le serveur Hostinger (en SSH) : il fait la mise à jour sur place ;
#   - depuis votre ordinateur : si SSH_HOTE est renseigné dans deploy.config,
#     il se connecte au serveur et y fait la même chose.
#
#  Réglages : deploy.config (modèle : deploy.config.example). Sans ce fichier,
#  les valeurs par défaut du guide DEPLOIEMENT_HOSTINGER.md sont utilisées.
# =============================================================================

set -euo pipefail

# ----------------------------------------------------------------------------
#  Affichage
# ----------------------------------------------------------------------------
if [ -t 1 ] || [ "${REJCC_COULEURS:-}" = 1 ]; then
    C_TITRE=$'\033[1;34m'; C_OK=$'\033[32m'; C_AVERT=$'\033[33m'; C_ERR=$'\033[1;31m'; C_GRIS=$'\033[2m'; C_FIN=$'\033[0m'
else
    C_TITRE=''; C_OK=''; C_AVERT=''; C_ERR=''; C_GRIS=''; C_FIN=''
fi

AVERTISSEMENTS=()
etape()  { printf '\n%s▸ %s%s\n' "$C_TITRE" "$*" "$C_FIN"; }
ok()     { printf '  %s✓%s %s\n' "$C_OK" "$C_FIN" "$*"; }
info()   { printf '  %s%s%s\n' "$C_GRIS" "$*" "$C_FIN"; }
avert()  { printf '  %s!%s %s\n' "$C_AVERT" "$C_FIN" "$*"; AVERTISSEMENTS+=("$*"); }
echec()  { printf '\n%s✗ %s%s\n' "$C_ERR" "$*" "$C_FIN" >&2; exit 1; }

aide() {
    cat <<'AIDE'
Usage : ./deploy.sh [options]

  (sans option)        Met en ligne la dernière version de la branche configurée.
  --verifier           Contrôle la configuration et affiche ce qui serait mis en
                       ligne, sans rien modifier.
  --forcer             Relance toutes les étapes même si le serveur est déjà à jour.
  --sans-sauvegarde    Ne sauvegarde pas les bases de données (déconseillé).
  --sans-maintenance   Laisse le site ouvert pendant la mise à jour.
  --aide               Affiche cette aide.

Étapes : contrôles → sauvegarde des bases → page de maintenance → code (git) →
dépendances (composer) → migrations → CSS/JS (npm run build) → caches →
réouverture → tâche planifiée (cron) → vérification des adresses publiques.
AIDE
}

# ----------------------------------------------------------------------------
#  Options et configuration
# ----------------------------------------------------------------------------
ARGS=("$@")
VERIFIER=0; FORCER=0; SANS_SAUVEGARDE=0; SANS_MAINTENANCE=0
for a in "$@"; do
    case "$a" in
        --verifier) VERIFIER=1 ;;
        --forcer) FORCER=1 ;;
        --sans-sauvegarde) SANS_SAUVEGARDE=1 ;;
        --sans-maintenance) SANS_MAINTENANCE=1 ;;
        -h|--aide|--help) aide; exit 0 ;;
        *) echec "Option inconnue : $a (voir ./deploy.sh --aide)" ;;
    esac
done

# Sur le serveur lancé depuis un ordinateur, la configuration est transmise
# avec le script (REJCC_CONFIG_TRANSMISE) : pas de fichier à relire.
if [ "${REJCC_CONFIG_TRANSMISE:-}" != 1 ]; then
    DOSSIER_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    FICHIER_CONFIG="${REJCC_DEPLOY_CONFIG:-$DOSSIER_SCRIPT/deploy.config}"
    if [ -f "$FICHIER_CONFIG" ]; then
        # shellcheck source=/dev/null
        . "$FICHIER_CONFIG"
    else
        FICHIER_CONFIG=""
    fi
fi

: "${SSH_HOTE:=}"
: "${SSH_PORT:=65002}"
: "${SSH_UTILISATEUR:=}"
: "${BRANCHE:=main}"
: "${DOSSIER_BACKEND:=$HOME/domains/api.rejcc.site/repo-backend/REJCC-Backend}"
: "${DOSSIER_FRONTEND:=$HOME/domains/rejcc.site/repo-frontend/REJCC-Frontend}"
: "${URL_SITE:=https://rejcc.site}"
: "${URL_API:=https://api.rejcc.site/api}"
: "${PHP:=php}"
: "${COMPOSER_BIN:=}"
: "${ASSETS:=auto}"
: "${SAUVEGARDE:=oui}"
: "${DOSSIER_SAUVEGARDES:=$HOME/sauvegardes-rejcc}"
: "${SAUVEGARDES_A_GARDER:=10}"
: "${MAINTENANCE:=oui}"

[ "$SAUVEGARDE" = oui ] && [ "$SANS_SAUVEGARDE" = 0 ] && FAIRE_SAUVEGARDE=1 || FAIRE_SAUVEGARDE=0
[ "$MAINTENANCE" = oui ] && [ "$SANS_MAINTENANCE" = 0 ] && FAIRE_MAINTENANCE=1 || FAIRE_MAINTENANCE=0

case "$ASSETS" in auto|serveur|local|recus) ;; *) echec "ASSETS doit valoir auto, serveur ou local (actuellement : $ASSETS)." ;; esac

# Code de sortie convenu : le serveur n'a pas Node.js, il faut construire les
# fichiers CSS/JS sur l'ordinateur.
CODE_ASSETS_LOCAUX=42

# =============================================================================
#  MODE ORDINATEUR : connexion SSH puis exécution sur le serveur
# =============================================================================

# Construit public/build sur l'ordinateur, à partir de la version exacte de la
# branche publiée sur GitHub (copie de travail séparée dans .deploy/, qui ne
# touche pas à vos fichiers en cours).
construire_assets_localement() {
    etape "Construction des fichiers CSS/JS sur cet ordinateur"
    command -v npm >/dev/null 2>&1 || echec "Node.js (npm) est introuvable sur cet ordinateur. Installez-le depuis https://nodejs.org puis relancez."
    local racine arbre sha
    racine="$(git -C "$DOSSIER_SCRIPT" rev-parse --show-toplevel)"
    git -C "$racine" fetch -q origin "$BRANCHE"
    sha="$(git -C "$racine" rev-parse FETCH_HEAD)"
    arbre="$racine/.deploy/arbre"
    if [ -e "$arbre/.git" ]; then
        git -C "$arbre" checkout -q --detach -f "$sha"
    else
        rm -rf "$arbre"; mkdir -p "$racine/.deploy"
        git -C "$racine" worktree prune
        git -C "$racine" worktree add -q --detach "$arbre" "$sha"
    fi
    info "Version : $(git -C "$arbre" log -1 --format='%h %s')"
    (
        cd "$arbre/REJCC-Frontend"
        empreinte="$(git hash-object package-lock.json)"
        if [ ! -d node_modules ] || [ "$(cat node_modules/.empreinte-rejcc 2>/dev/null)" != "$empreinte" ]; then
            info "Installation des dépendances (npm ci)…"
            sortie="$(npm ci --no-audit --no-fund 2>&1)" || { printf '%s\n' "$sortie" | tail -n 30; echec "npm ci a échoué."; }
            echo "$empreinte" > node_modules/.empreinte-rejcc
        fi
        sortie="$(npm run build 2>&1)" || { printf '%s\n' "$sortie" | tail -n 30; echec "npm run build a échoué."; }
        echo "$sha" > public/build/.commit-rejcc
        tar -czf "$racine/.deploy/build.tar.gz" -C public build
    )
    ok "Fichiers prêts ($(du -h "$racine/.deploy/build.tar.gz" | cut -f1))"
    ARCHIVE_ASSETS="$racine/.deploy/build.tar.gz"
}

lancer_sur_le_serveur() {
    local cible="$SSH_HOTE" mode_assets="$1" charge commande
    [ -n "$SSH_UTILISATEUR" ] && cible="$SSH_UTILISATEUR@$SSH_HOTE"
    # Le serveur reçoit la configuration suivie du script lui-même : il n'a
    # besoin d'aucun fichier préalable et utilise toujours cette version-ci.
    charge="REJCC_CONFIG_TRANSMISE=1"$'\n'
    [ -n "$FICHIER_CONFIG" ] && charge+="$(cat "$FICHIER_CONFIG")"$'\n'
    charge+="ASSETS=$mode_assets"$'\n'
    charge+="$(cat "$DOSSIER_SCRIPT/deploy.sh")"
    commande="REJCC_SUR_SERVEUR=1 REJCC_COULEURS=$([ -t 1 ] && echo 1 || echo 0) bash -c $(printf '%q' "$charge") deploy.sh"
    [ "${#ARGS[@]}" -gt 0 ] && commande+=" $(printf '%q ' "${ARGS[@]}")"
    if [ "$mode_assets" = recus ]; then
        ssh -p "$SSH_PORT" -o ServerAliveInterval=30 "$cible" "$commande" < "$ARCHIVE_ASSETS"
    else
        ssh -p "$SSH_PORT" -o ServerAliveInterval=30 "$cible" "$commande" < /dev/null
    fi
}

# Mode ordinateur : SSH_HOTE renseigné, et on n'est pas déjà sur le serveur
# (le dossier du backend n'existe pas ici).
if [ "${REJCC_SUR_SERVEUR:-}" != 1 ] && [ -n "$SSH_HOTE" ] && [ ! -f "$DOSSIER_BACKEND/artisan" ]; then
    command -v ssh >/dev/null 2>&1 || echec "La commande ssh est introuvable sur cet ordinateur."
    printf '%sREJCC : mise en production via %s (port %s)%s\n' "$C_TITRE" "$SSH_HOTE" "$SSH_PORT" "$C_FIN"
    if [ "$ASSETS" = local ] && [ "$VERIFIER" = 0 ]; then
        construire_assets_localement
        lancer_sur_le_serveur recus
        exit $?
    fi
    code=0
    lancer_sur_le_serveur "$ASSETS" || code=$?
    if [ "$code" = "$CODE_ASSETS_LOCAUX" ]; then
        info "Astuce : mettez ASSETS=\"local\" dans deploy.config pour éviter cette double connexion."
        construire_assets_localement
        lancer_sur_le_serveur recus
        exit $?
    fi
    exit "$code"
fi

# =============================================================================
#  MODE SERVEUR
# =============================================================================

# Archive CSS/JS envoyée par l'ordinateur : on la lit avant toute autre chose.
TMP="$(mktemp -d "${TMPDIR:-/tmp}/rejcc-deploy.XXXXXX")"
VERROU="$HOME/.rejcc-deploy.lock"
VERROU_PRIS=0
EN_MAINTENANCE=()
nettoyer() {
    local code=$?
    for d in "${EN_MAINTENANCE[@]}"; do
        (cd "$d" && "$PHP" artisan up >/dev/null 2>&1) || true
    done
    [ "${#EN_MAINTENANCE[@]}" -gt 0 ] && [ "$code" != 0 ] && printf '  %sLe site a été rouvert.%s\n' "$C_AVERT" "$C_FIN" >&2
    [ "$VERROU_PRIS" = 1 ] && rm -rf "$VERROU"
    rm -rf "$TMP"
    if [ "$code" != 0 ] && [ "$code" != "$CODE_ASSETS_LOCAUX" ]; then
        printf '\n%sLa mise en production s'"'"'est arrêtée (code %s).%s\n' "$C_ERR" "$code" "$C_FIN" >&2
        [ -n "${JOURNAL:-}" ] && printf '  Journal complet : %s\n' "$JOURNAL" >&2
        [ -n "${SAUVEGARDES_FAITES:-}" ] && printf '  Sauvegardes des bases : %s\n' "$SAUVEGARDES_FAITES" >&2
    fi
}
trap nettoyer EXIT
trap 'echec "Erreur à la ligne $LINENO : $BASH_COMMAND"' ERR

if [ "$ASSETS" = recus ]; then
    cat > "$TMP/build.tar.gz"
fi
exec </dev/null

# Node.js installé avec nvm n'est pas chargé dans une session SSH non interactive.
if ! command -v npm >/dev/null 2>&1 && [ -s "$HOME/.nvm/nvm.sh" ]; then
    # shellcheck source=/dev/null
    . "$HOME/.nvm/nvm.sh" >/dev/null 2>&1 || true
fi

artisan() { "$PHP" artisan "$@" --no-interaction; }

# Lit une valeur dans un fichier .env sans l'exécuter.
lire_env() {
    local valeur
    valeur="$(grep -E "^[[:space:]]*$2[[:space:]]*=" "$1" 2>/dev/null | tail -n 1 | cut -d= -f2- || true)"
    valeur="${valeur%$'\r'}"
    valeur="$(printf '%s' "$valeur" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+#.*$//; s/[[:space:]]+$//')"
    case "$valeur" in
        \"*\") valeur="${valeur:1:${#valeur}-2}" ;;
        \'*\') valeur="${valeur:1:${#valeur}-2}" ;;
    esac
    printf '%s' "$valeur"
}

printf '%sREJCC : mise en production (%s)%s\n' "$C_TITRE" "$(hostname 2>/dev/null || echo serveur)" "$C_FIN"
[ "$VERIFIER" = 1 ] && info "Mode vérification : aucune modification ne sera faite."
DEBUT=$(date +%s)

# ----------------------------------------------------------------------------
etape "Contrôles"
# ----------------------------------------------------------------------------
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    [ -f "$d/artisan" ] || echec "Dossier Laravel introuvable : $d (vérifiez DOSSIER_BACKEND / DOSSIER_FRONTEND dans deploy.config)."
    [ -f "$d/.env" ] || echec "Fichier .env absent dans $d (voir l'étape 5 de DEPLOIEMENT_HOSTINGER.md)."
done
ok "Dossiers backend et frontend trouvés"

command -v "$PHP" >/dev/null 2>&1 || echec "PHP introuvable ($PHP). Indiquez le bon chemin dans PHP= (deploy.config)."
VERSION_PHP="$("$PHP" -r 'echo PHP_VERSION_ID;')"
[ "$VERSION_PHP" -ge 80200 ] || echec "PHP $("$PHP" -r 'echo PHP_VERSION;') trop ancien : il faut PHP 8.2 ou plus. Indiquez par ex. PHP=\"/opt/alt/php83/usr/bin/php\" dans deploy.config."
ok "PHP $("$PHP" -r 'echo PHP_VERSION;')"

if [ -n "$COMPOSER_BIN" ]; then
    COMPOSER_CMD=("$COMPOSER_BIN")
elif command -v composer >/dev/null 2>&1; then
    COMPOSER_CMD=("$PHP" "$(command -v composer)")
elif command -v composer2 >/dev/null 2>&1; then
    COMPOSER_CMD=("$PHP" "$(command -v composer2)")
elif [ -f "$HOME/composer.phar" ]; then
    COMPOSER_CMD=("$PHP" "$HOME/composer.phar")
else
    echec "Composer introuvable. Indiquez son chemin dans COMPOSER_BIN= (deploy.config)."
fi
ok "Composer $("${COMPOSER_CMD[@]}" --version --no-ansi 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n 1)"

# Fichiers CSS/JS : sur le serveur, ou reçus de l'ordinateur.
case "$ASSETS" in
    recus)
        tar -tzf "$TMP/build.tar.gz" build/manifest.json >/dev/null 2>&1 || echec "L'archive CSS/JS reçue est incomplète."
        ok "Fichiers CSS/JS reçus de l'ordinateur" ;;
    serveur|auto)
        if command -v npm >/dev/null 2>&1; then
            ASSETS=serveur
            ok "Node.js $(node -v) : les fichiers CSS/JS seront construits sur le serveur"
        elif [ "$ASSETS" = auto ] && [ -z "${REJCC_SUR_SERVEUR:-}" ]; then
            echec "Node.js est absent du serveur. Lancez ./deploy.sh depuis votre ordinateur (SSH_HOTE dans deploy.config) : il construira les fichiers CSS/JS et les enverra."
        elif [ "$ASSETS" = auto ]; then
            info "Node.js absent du serveur : les fichiers CSS/JS seront construits sur votre ordinateur."
            [ "$VERIFIER" = 1 ] && ok "Contrôles terminés (Node.js à utiliser côté ordinateur)" || exit "$CODE_ASSETS_LOCAUX"
        else
            echec "ASSETS=\"serveur\" mais Node.js (npm) est introuvable sur le serveur."
        fi ;;
    local)
        [ "$VERIFIER" = 1 ] || echec "ASSETS=\"local\" : lancez ./deploy.sh depuis votre ordinateur (SSH_HOTE dans deploy.config)." ;;
esac

# Réglages indispensables des .env
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    nom="$(basename "$d")"
    [ -n "$(lire_env "$d/.env" APP_KEY)" ] || echec "$nom : APP_KEY est vide dans .env (lancez « php artisan key:generate » dans $d)."
    [ "$(lire_env "$d/.env" APP_DEBUG)" = true ] && avert "$nom : APP_DEBUG=true dans .env. En production, mettez APP_DEBUG=false (sinon les erreurs affichent des informations internes)."
    [ "$(lire_env "$d/.env" APP_ENV)" = production ] || avert "$nom : APP_ENV n'est pas « production » dans .env."
done
[ -n "$(lire_env "$DOSSIER_FRONTEND/.env" BACKEND_API_URL)" ] || echec "REJCC-Frontend : BACKEND_API_URL est vide dans .env (ex. https://api.rejcc.site/api)."
[ -n "$(lire_env "$DOSSIER_BACKEND/.env" CERTIFICATS_CLE_SIGNATURE)" ] || avert "REJCC-Backend : CERTIFICATS_CLE_SIGNATURE est vide dans .env. Les certificats ne seront pas signés électroniquement."
case "$(lire_env "$DOSSIER_BACKEND/.env" MAIL_MAILER)" in
    ''|log|array) avert "REJCC-Backend : MAIL_MAILER n'envoie pas de vrais e-mails (valeur actuelle : « $(lire_env "$DOSSIER_BACKEND/.env" MAIL_MAILER) »). Configurez le SMTP Hostinger dans .env." ;;
esac
EXPEDITEUR="$(lire_env "$DOSSIER_BACKEND/.env" MAIL_FROM_NAME)"
[ "$EXPEDITEUR" = '${APP_NAME}' ] && EXPEDITEUR="$(lire_env "$DOSSIER_BACKEND/.env" APP_NAME)"
case "$EXPEDITEUR" in
    *API*|*Laravel*) avert "REJCC-Backend : les e-mails partiraient au nom de « $EXPEDITEUR ». Mettez MAIL_FROM_NAME=\"REJCC\" dans .env." ;;
esac
case "$(lire_env "$DOSSIER_BACKEND/.env" MAIL_FROM_ADDRESS)" in
    *example.com*|'') avert "REJCC-Backend : MAIL_FROM_ADDRESS n'est pas une adresse du REJCC (ex. contact@rejcc.site)." ;;
esac
ok "Fichiers .env contrôlés"

# Dépôts git : un seul si backend et frontend partagent le même clone.
DEPOTS=()
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    r="$(git -C "$d" rev-parse --show-toplevel 2>/dev/null)" || echec "$d n'est pas dans un dépôt git."
    [[ " ${DEPOTS[*]-} " == *" $r "* ]] || DEPOTS+=("$r")
done

declare -A AVANT APRES
A_METTRE_A_JOUR=0
for r in "${DEPOTS[@]}"; do
    git -C "$r" fetch -q origin "$BRANCHE" || echec "Impossible de récupérer la branche $BRANCHE depuis GitHub ($r)."
    AVANT[$r]="$(git -C "$r" rev-parse HEAD)"
    APRES[$r]="$(git -C "$r" rev-parse FETCH_HEAD)"
    modifies="$(git -C "$r" status --porcelain --untracked-files=no)"
    if [ -n "$modifies" ]; then
        printf '%s\n' "$modifies" | sed 's/^/      /'
        echec "Des fichiers du dépôt ont été modifiés directement sur le serveur ($r). Mettez-les de côté (git stash) ou annulez-les (git checkout -- .) puis relancez."
    fi
    git -C "$r" merge-base --is-ancestor "${AVANT[$r]}" "${APRES[$r]}" \
        || echec "La version du serveur ($r) contient des commits absents de GitHub : mise à jour automatique impossible sans risque."
    if [ "${AVANT[$r]}" != "${APRES[$r]}" ]; then
        A_METTRE_A_JOUR=1
        info "$(basename "$(dirname "$r")")/$(basename "$r") : $(git -C "$r" rev-list --count "${AVANT[$r]}..${APRES[$r]}") nouveau(x) commit(s)"
        git -C "$r" log --format='      %h %s' "${AVANT[$r]}..${APRES[$r]}" | head -n 15
    fi
done
ok "Dépôts git propres et à jour avec GitHub ($BRANCHE)"

if [ "$ASSETS" = recus ]; then
    commit_archive="$(tar -xzOf "$TMP/build.tar.gz" build/.commit-rejcc 2>/dev/null || true)"
    racine_front="$(git -C "$DOSSIER_FRONTEND" rev-parse --show-toplevel)"
    [ "$commit_archive" = "${APRES[$racine_front]}" ] \
        || echec "Les fichiers CSS/JS envoyés ne correspondent pas à la dernière version de $BRANCHE. Relancez ./deploy.sh."
fi

if [ "$VERIFIER" = 1 ]; then
    [ "$A_METTRE_A_JOUR" = 1 ] && info "Une mise à jour est disponible." || info "Le serveur est déjà à jour."
    printf '\n%sVérification terminée : tout est prêt.%s\n' "$C_OK" "$C_FIN"
    exit 0
fi

if [ "$A_METTRE_A_JOUR" = 0 ] && [ "$FORCER" = 0 ]; then
    printf '\n%sLe serveur est déjà à jour (%s). Rien à faire.%s\n' "$C_OK" "$(git -C "${DEPOTS[0]}" log -1 --format='%h %s')" "$C_FIN"
    info "Pour tout relancer quand même : ./deploy.sh --forcer"
    exit 0
fi

# Un seul déploiement à la fois.
if mkdir "$VERROU" 2>/dev/null; then
    VERROU_PRIS=1
else
    echec "Un autre déploiement semble en cours. S'il a été interrompu, supprimez le dossier $VERROU puis relancez."
fi

mkdir -p "$DOSSIER_SAUVEGARDES/journaux"
chmod 700 "$DOSSIER_SAUVEGARDES"
HORODATAGE="$(date +%Y%m%d-%H%M%S)"
JOURNAL="$DOSSIER_SAUVEGARDES/journaux/deploy-$HORODATAGE.log"
exec > >(tee -a "$JOURNAL") 2>&1
for r in "${DEPOTS[@]}"; do
    info "Version avant mise à jour ($r) : ${AVANT[$r]}  →  nouvelle : ${APRES[$r]}"
done

# ----------------------------------------------------------------------------
if [ "$FAIRE_SAUVEGARDE" = 1 ]; then
etape "Sauvegarde des bases de données"
# ----------------------------------------------------------------------------
    SAUVEGARDES_FAITES="$DOSSIER_SAUVEGARDES"
    for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
        nom="$(basename "$d" | tr '[:upper:]' '[:lower:]' | sed 's/^rejcc-//')"
        cible="$DOSSIER_SAUVEGARDES/$HORODATAGE-$nom"
        cp "$d/.env" "$cible.env"; chmod 600 "$cible.env"
        connexion="$(lire_env "$d/.env" DB_CONNECTION)"
        case "$connexion" in
            mysql|mariadb)
                command -v mysqldump >/dev/null 2>&1 || echec "mysqldump introuvable : sauvegarde impossible (relancez avec --sans-sauvegarde à vos risques)."
                identifiants="$TMP/mysql-$nom.cnf"
                ( umask 077
                  printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\n' \
                    "$(lire_env "$d/.env" DB_HOST)" "$(lire_env "$d/.env" DB_PORT)" \
                    "$(lire_env "$d/.env" DB_USERNAME)" "$(lire_env "$d/.env" DB_PASSWORD | sed 's/\\/\\\\/g; s/"/\\"/g')" > "$identifiants" )
                mysqldump --defaults-extra-file="$identifiants" --single-transaction --quick --no-tablespaces \
                    "$(lire_env "$d/.env" DB_DATABASE)" | gzip > "$cible.sql.gz"
                ;;
            sqlite|'')
                base="$(lire_env "$d/.env" DB_DATABASE)"
                [ -z "$base" ] && base="$d/database/database.sqlite"
                [ "${base:0:1}" = / ] || base="$d/$base"
                [ -f "$base" ] || echec "Base SQLite introuvable : $base"
                gzip -c "$base" > "$cible.sqlite.gz"
                ;;
            *) echec "Type de base non géré pour la sauvegarde : $connexion ($d)" ;;
        esac
        chmod 600 "$cible".*
        ok "$nom → $(ls "$cible".*.gz) ($(du -h "$cible".*.gz | cut -f1))"
    done
    # On ne garde que les plus récentes.
    for nom in backend frontend; do
        ls -1t "$DOSSIER_SAUVEGARDES"/*-"$nom".*.gz 2>/dev/null | tail -n +"$((SAUVEGARDES_A_GARDER + 1))" | while read -r f; do
            rm -f "$f" "${f%%.sql.gz}.env" "${f%%.sqlite.gz}.env"
        done || true
    done
    ls -1t "$DOSSIER_SAUVEGARDES"/journaux/deploy-*.log 2>/dev/null | tail -n +31 | xargs -r rm -f || true
fi

# ----------------------------------------------------------------------------
if [ "$FAIRE_MAINTENANCE" = 1 ]; then
etape "Page de maintenance"
# ----------------------------------------------------------------------------
    for d in "$DOSSIER_FRONTEND" "$DOSSIER_BACKEND"; do
        (cd "$d" && artisan down --retry=60 >/dev/null)
        EN_MAINTENANCE+=("$d")
    done
    ok "Site et API en maintenance"
fi

# ----------------------------------------------------------------------------
etape "Code ($BRANCHE)"
# ----------------------------------------------------------------------------
for r in "${DEPOTS[@]}"; do
    git -C "$r" merge -q --ff-only "${APRES[$r]}"
    ok "$(basename "$(dirname "$r")")/$(basename "$r") → $(git -C "$r" log -1 --format='%h %s')"
done

# ----------------------------------------------------------------------------
etape "Dépendances PHP (composer)"
# ----------------------------------------------------------------------------
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    sortie="$(cd "$d" && "${COMPOSER_CMD[@]}" install --no-dev --optimize-autoloader --no-interaction --no-progress --no-ansi 2>&1)" \
        || { printf '%s\n' "$sortie" | tail -n 30; echec "composer install a échoué ($(basename "$d"))."; }
    ok "$(basename "$d")"
done

# ----------------------------------------------------------------------------
etape "Base de données (migrations)"
# ----------------------------------------------------------------------------
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    sortie="$(cd "$d" && artisan migrate --force 2>&1)" || { printf '%s\n' "$sortie"; echec "Migration échouée ($(basename "$d")). Le site est rouvert ; la sauvegarde de la base est dans $DOSSIER_SAUVEGARDES."; }
    n="$(printf '%s\n' "$sortie" | grep -c 'DONE' || true)"
    [ "$n" -gt 0 ] && ok "$(basename "$d") : $n migration(s) appliquée(s)" || ok "$(basename "$d") : rien à migrer"
done

# ----------------------------------------------------------------------------
etape "Fichiers CSS/JS"
# ----------------------------------------------------------------------------
if [ "$ASSETS" = recus ]; then
    rm -rf "$TMP/assets" && mkdir -p "$TMP/assets"
    tar -xzf "$TMP/build.tar.gz" -C "$TMP/assets"
    rm -rf "$DOSSIER_FRONTEND/public/build.ancien"
    [ -d "$DOSSIER_FRONTEND/public/build" ] && mv "$DOSSIER_FRONTEND/public/build" "$DOSSIER_FRONTEND/public/build.ancien"
    mv "$TMP/assets/build" "$DOSSIER_FRONTEND/public/build"
    rm -rf "$DOSSIER_FRONTEND/public/build.ancien"
    ok "Fichiers reçus installés"
else
    empreinte="$(git -C "$DOSSIER_FRONTEND" hash-object package-lock.json)"
    if [ ! -d "$DOSSIER_FRONTEND/node_modules" ] || [ "$(cat "$DOSSIER_FRONTEND/node_modules/.empreinte-rejcc" 2>/dev/null)" != "$empreinte" ]; then
        sortie="$(cd "$DOSSIER_FRONTEND" && npm ci --no-audit --no-fund 2>&1)" \
            || { printf '%s\n' "$sortie" | tail -n 30; echec "npm ci a échoué."; }
        echo "$empreinte" > "$DOSSIER_FRONTEND/node_modules/.empreinte-rejcc"
        ok "npm ci (dépendances mises à jour)"
    fi
    sortie="$(cd "$DOSSIER_FRONTEND" && npm run build 2>&1)" \
        || { printf '%s\n' "$sortie" | tail -n 30; echec "npm run build a échoué."; }
    ok "npm run build"
fi
[ -f "$DOSSIER_FRONTEND/public/build/manifest.json" ] || echec "public/build/manifest.json absent après la construction."

# ----------------------------------------------------------------------------
etape "Réglages et caches Laravel"
# ----------------------------------------------------------------------------
(cd "$DOSSIER_FRONTEND" && { [ -e public/storage ] || artisan storage:link >/dev/null; })
(cd "$DOSSIER_FRONTEND" && artisan pieces:securiser | sed 's/^/  /')
for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
    chmod -R ug+rwX "$d/storage" "$d/bootstrap/cache" 2>/dev/null || true
    # Pas de « optimize » global : l'API n'a pas de dossier resources/views.
    caches=(config:cache route:cache event:cache)
    [ -d "$d/resources/views" ] && caches+=(view:cache)
    sortie="$(cd "$d" && artisan optimize:clear 2>&1 && for c in "${caches[@]}"; do artisan "$c" 2>&1 || exit 1; done)" \
        || { printf '%s\n' "$sortie" | tail -n 30; echec "Reconstruction des caches échouée ($(basename "$d"))."; }
    ok "$(basename "$d") : caches reconstruits"
done

# ----------------------------------------------------------------------------
if [ "${#EN_MAINTENANCE[@]}" -gt 0 ]; then
etape "Réouverture du site"
# ----------------------------------------------------------------------------
    for d in "$DOSSIER_BACKEND" "$DOSSIER_FRONTEND"; do
        (cd "$d" && artisan up >/dev/null)
    done
    EN_MAINTENANCE=()
    ok "Site et API rouverts"
fi

# ----------------------------------------------------------------------------
etape "Tâche planifiée (rappels, e-mails, certificats…)"
# ----------------------------------------------------------------------------
LIGNE_CRON="* * * * * cd $DOSSIER_BACKEND && $(command -v "$PHP") artisan schedule:run >> /dev/null 2>&1"
if crontab -l 2>/dev/null | grep -q 'schedule:run'; then
    ok "Déjà en place"
elif command -v crontab >/dev/null 2>&1 && { crontab -l 2>/dev/null; echo "$LIGNE_CRON"; } | crontab - 2>/dev/null \
        && crontab -l 2>/dev/null | grep -qF "$LIGNE_CRON"; then
    ok "Ajoutée (chaque minute) : $LIGNE_CRON"
else
    avert "Tâche planifiée à créer dans hPanel → Avancé → Tâches Cron, « chaque minute », commande : cd $DOSSIER_BACKEND && $(command -v "$PHP") artisan schedule:run"
fi

# ----------------------------------------------------------------------------
etape "Vérification des adresses publiques"
# ----------------------------------------------------------------------------
verifier_url() {
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$1" || true)"
    if [ "$code" = 200 ]; then ok "$1 → 200"; else avert "$1 répond « ${code:-aucune réponse} » au lieu de 200."; fi
}
if command -v curl >/dev/null 2>&1; then
    verifier_url "$URL_API/home-content"
    verifier_url "$URL_SITE/"
else
    info "curl absent : ouvrez $URL_SITE dans votre navigateur pour vérifier."
fi

# ----------------------------------------------------------------------------
DUREE=$(( $(date +%s) - DEBUT ))
printf '\n%s✓ Mise en production terminée en %d min %02d s.%s\n' "$C_OK" $((DUREE / 60)) $((DUREE % 60)) "$C_FIN"
info "Version en ligne : $(git -C "${DEPOTS[0]}" log -1 --format='%h %s (%cd)' --date=format:'%d/%m/%Y %H:%M')"
[ "$FAIRE_SAUVEGARDE" = 1 ] && info "Sauvegardes : $DOSSIER_SAUVEGARDES"
info "Journal : $JOURNAL"
if [ "${#AVERTISSEMENTS[@]}" -gt 0 ]; then
    printf '\n%sÀ regarder :%s\n' "$C_AVERT" "$C_FIN"
    for a in "${AVERTISSEMENTS[@]}"; do printf '  - %s\n' "$a"; done
fi
