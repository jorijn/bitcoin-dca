#!/usr/bin/env bash

###########################################################################################
# Bitcoin DCA for Linux installation script
#
# NOTE: Make sure to verify the contents of the script
#       you downloaded matches the contents of setup.sh
#       located at https://github.com/Jorijn/bitcoin-dca/blob/master/setup.sh
#       before executing.
###########################################################################################

trap 'show_support_text' ERR
trap 'remove_temp_configuration_file' EXIT
trap 'exit' SIGINT

RED=$(tput setaf 1)
GREEN=$(tput setaf 2)
NC=$(tput sgr0)
DOCKER_IMAGE="jorijn/bitcoin-dca:latest"
APPLICATION_DIR="${HOME}/.bitcoin-dca"
FLAG_PERSISTENT_STORAGE=0
CONFIGURATION_FILE=$(mktemp)

function show_support_text() {
  echo
  echo "${RED}-------------------------------------------------------------------------------------${NC}"
  echo "${RED}[!]${NC} It looks like something went wrong. If you are unsure what happened, feel free to"
  echo "${RED}[!]${NC} reach out to me by email at jorijn@jorijn.com or visit the Telegram support chat"
  echo "${RED}[!]${NC} at https://t.me/bitcoindca"

  remove_temp_configuration_file

  exit 1
}

command_exists() {
  command -v "$@" >/dev/null 2>&1
}

check_if_running_under_root() {
  if [[ $EUID -eq 0 ]]; then
    echo
    echo "Please do not run this script as root. ☹️"

    show_support_text
  fi

  return 0
}

function exit() {
  remove_temp_configuration_file

  exit
}

check_docker_runnable_under_own_user() {
  if docker version &>/dev/null; then
    return 0
  fi

  your_user=your-user
  [ "$user" != 'root' ] && your_user="$user"
  # intentionally mixed spaces and tabs here -- tabs are stripped by "<<-EOF", spaces are kept in the output
  echo
  echo "${GREEN}==================================================================================================${NC}"
  echo "${GREEN}⚠️  It looks like we need a manual step before going further.${NC}"
  echo "${GREEN}==================================================================================================${NC}"
  echo "For scheduling Docker tasks under your own user, you should add your own user to the Docker group:"
  echo
  echo "$ sudo usermod -aG docker $your_user"
  echo
  echo "Remember that you will have to log out and back in for this to take effect!"
  echo "Re-run the setup script after your done."
  echo
  echo "WARNING: Adding a user to the \"docker\" group will grant the ability to run"
  echo "         containers which can be used to obtain root privileges on the"
  echo "         docker host."
  echo "         Refer to https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface"
  echo "         for more information."

  show_support_text
}

function check_if_docker_is_installed() {
  if command_exists docker; then
    return 0
  fi

  echo "To run Bitcoin DCA you need Docker and it looks like Docker is not installed on this machine."
  echo
  echo "To install Docker, run the following commands:"
  echo
  echo "$ curl -fsSL https://get.docker.com -o get-docker.sh"
  echo "$ sh get-docker.sh"

  show_support_text
}

function pull_image_from_registry() {
  echo
  docker pull $DOCKER_IMAGE
  echo
}

function check_application_directory() {
  if [ ! -e "$APPLICATION_DIR" ]; then
    return 0
  fi

  echo
  echo "🤔 It looks like the application directory ${GREEN}${APPLICATION_DIR}${NC} already exists.."
  echo "If you want to setup a new installation please remove this directory before going any further."

  show_support_text
}

function verify() {
  while true; do
    read -rp "❓ $1 [y/n]: " yn

    case $yn in
    [Yy]*)
      return 0
      break
      ;;
    [Nn]*)
      return 1
      break
      ;;
    *) echo "Please answer yes or no." ;;
    esac
  done
}

function ask() {
  while true; do
    read -rp "❓ $1: " answer

    if [ -n "$answer" ]; then
      echo "$answer"
      break
    fi

    if [ -n "$2" ]; then
      echo "$2"
      break
    fi
  done
}

function configuration_pick_an_exchange() {
  echo
  echo "💵 First, we need to pick on which Exchange the Bitcoin DCA tool will run."

  PS3='Please enter your choice: '
  options=("BL3P" "Bitvavo" "I changed my mind, quit")
  select opt in "${options[@]}"; do
    case $opt in
    "BL3P")
      configure_exchange_bl3p
      return
      ;;
    "Bitvavo")
      configure_exchange_bitvavo
      return
      ;;
    "I changed my mind, quit")
      exit 0
      ;;
    *) echo "☠️ Invalid option: $REPLY" ;;
    esac
  done
}

function start_configuration_wizard() {
  configuration_pick_an_exchange

  # more...
}

function remove_temp_configuration_file() {
  cat "$CONFIGURATION_FILE" # debug

  rm "$CONFIGURATION_FILE"
}

function configure_exchange_bl3p() {
  local api_key
  local api_secret

  echo
  echo "EXCHANGE=bl3p" >> "$CONFIGURATION_FILE"

  echo "💵 Great choice! Let's configure the API connection."
  echo

  api_key=$(ask "What is your BL3P API Identifier Key")
  echo "BL3P_PUBLIC_KEY=$api_key" >> "$CONFIGURATION_FILE"

  api_secret=$(ask "What is your BL3P API Private Key")
  echo "BL3P_PRIVATE_KEY=$api_secret" >> "$CONFIGURATION_FILE"
}

function configure_exchange_bitvavo() {
  echo "EXCHANGE=bitvavo" >"$CONFIGURATION_FILE"
}

do_install() {
  echo "🤖 Checking if we are not running under root.."
  check_if_running_under_root

  echo "🤖 Checking if Docker is installed.."
  check_if_docker_is_installed

  echo "🤖 Checking if your local user can talk to the Docker daemon.."
  check_docker_runnable_under_own_user

  echo "🤖 Pulling the Bitcoin DCA tool from the registry.."
  pull_image_from_registry

  echo "🤖 Setting up the application directory.."
  check_application_directory

  echo "🤖 Starting the configuration wizard.."
  start_configuration_wizard
}

echo "${GREEN}====================================================================================${NC}"
echo "${GREEN}💰 Installation script for Bitcoin DCA — https://github.com/Jorijn/bitcoin-dca"
echo "${GREEN}====================================================================================${NC}"

# wrapped up in a function so that we have some protection against only getting
# half the file during "curl | sh"
do_install
