.DEFAULT_GOAL := help
.PHONY: $(filter-out vendor node_modules,$(MAKECMDGOALS))

bin = vendor/bin

help: ## This help message
	@printf "\033[33mUsage:\033[0m\n  make [target]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

# Aliases
precommit: cs-fixer scan ## lint phpstan ## Run style fixing and linting commands
scan: lint phpmd phpstan ## Run all scans including mess detection and static analysis
baseline: phpstan-baseline phpmd-baseline ## Generate baselines for mess detection and static analysis

# Build Tooling
cs-fixer: ## Code styling fixer
	$(bin)/php-cs-fixer fix --config=.php-cs-fixer.dist.php

lint: ## PHP, YAML & Twig Syntax Checking
	@$(bin)/parallel-lint -j 10 src/ --no-progress --colors --blame

lint-ci:
	$(bin)/parallel-lint -j 10 src/ --no-progress --colors --checkstyle > report.xml

phpmd: ## PHP Mess Detection
	$(bin)/phpmd src/ ansi phpmd.dist.xml

phpmd-ci:
	$(bin)/phpmd src/ github phpmd.dist.xml

phpmd-baseline: ## PHP Mess Detection. Generate Baseline
	$(bin)/phpmd src/ ansi phpmd.dist.xml --generate-baseline

phpstan: ## PHP Static Analyzer
	$(bin)/phpstan analyse --error-format=table --configuration=./phpstan.dist.neon

phpstan-ci:
	$(bin)/phpstan analyse --no-progress --error-format=github --configuration=phpstan.dist.neon

phpstan-baseline: ## PHP Static Analyzer. Generate Baseline.
	$(bin)/phpstan analyse --error-format=table --configuration=phpstan.dist.neon --generate-baseline=phpstan-baseline.neon --allow-empty-baseline
