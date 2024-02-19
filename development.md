# Codeception Module Foundry - Development

## PHP Version
You can use Symfony binary to help work in many different PHP versions, if they are all installed locally. By adding
a .php-version file to the root directory you can have your terminal load the version specified in there.

** This is not required, and you can do this with different methods, just one of the many ways.

## PreCommit setup

Using the following example you can have a pre-commit hook that will run validation before committing. Make sure the
file is executable.

```shell
set -e

# Initial cache of files
INIT_FILES=$(git diff --name-only --cached)

# Run Make file
make precommit

# Get the list of modified files after running php-cs-fixer
MODIFIED_FILES=$(git diff --name-only --cached)

# Compare the new list to the original list and add the matching files to the staging area
for file in $MODIFIED_FILES; do
    for init_file in $INIT_FILES; do
        if [ "$file" = "$init_file" ]; then
            git add "$file"
            break
        fi
    done
done
```
