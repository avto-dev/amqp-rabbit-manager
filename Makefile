#!/usr/bin/make
# Makefile readme (ru): <http://linux.yaroslavl.ru/docs/prog/gnu_make_3-79_russian_manual.html>
# Makefile readme (en): <https://www.gnu.org/software/make/manual/html_node/index.html#SEC_Contents>

docker_bin := $(shell command -v docker 2> /dev/null)

SHELL = /bin/sh
BUILD_ARGS = -f ./docker/Dockerfile .
RUN_ARGS = --rm -v "$(shell pwd):/src:cached" -v "/etc/passwd:/etc/passwd:ro" -v "/etc/group:/etc/group:ro" \
           --workdir "/src" -u "$(shell id -u):$(shell id -g)" --tty --interactive

.PHONY : help update install test shell
.DEFAULT_GOAL : help

# This will output the help for each task. thanks to https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## Show this help
	@printf "\033[33m%s:\033[0m\n" 'Available commands'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[32m%-14s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build docker image with required for current package environment
	$(docker_bin) build $(BUILD_ARGS)

update: build ## Update all php dependencies
	$(docker_bin) run $(RUN_ARGS) $$($(docker_bin) build -q $(BUILD_ARGS)) \
	  composer update --no-interaction --ansi --no-suggest --prefer-dist

install: build ## Install all php dependencies
	$(docker_bin) run $(RUN_ARGS) $$($(docker_bin) build -q $(BUILD_ARGS)) \
	  composer install --no-interaction --ansi --no-suggest --prefer-dist

test:build  ## Execute php tests and linters
	$(docker_bin) run $(RUN_ARGS) $$($(docker_bin) build -q $(BUILD_ARGS)) \
	  sh -c "composer phpstan && composer test"

shell: build ## Start shell into container with php
	$(docker_bin) run $(RUN_ARGS) \
	  -e "PS1=\[\033[1;32m\]\[\033[1;36m\][\u@docker] \[\033[1;34m\]\w\[\033[0;35m\] \[\033[1;36m\]# \[\033[0m\]" \
      $$($(docker_bin) build -q $(BUILD_ARGS)) sh

clean: ## Remove all dependencies and unimportant files
	-rm -Rf ./composer.lock ./vendor ./coverage
