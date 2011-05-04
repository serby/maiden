_maiden()
{
	local cur prev opts
	cur="${COMP_WORDS[COMP_CWORD]}"
	prev="${COMP_WORDS[COMP_CWORD-1]}"
	opts="-h -l -b -v -q"
	if [ -e "Maiden.php" ]
	then
		targets=`echo ""| maiden -b | sort -u`
		COMPREPLY=( $(compgen -W "${targets}" -- ${cur}) )
		return 0
	fi
	COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
	return 0
}
complete -F _maiden maiden
