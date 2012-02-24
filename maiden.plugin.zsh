# maiden basic command completion

_maiden_get_targets_list () {
        maiden | awk '/^\t\t[a-z]+/ { print $1 }'
}

_maiden () {
  if [ -f Maiden.php ]; then
    compadd '_maiden_get_targets_list'
  fi
}

compdef _maiden maiden
