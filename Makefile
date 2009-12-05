# This mainly takes care of produced code/files, like the gettext ones.

SRC_DOMAINS=a2blib

LANGS-a2blib=el_GR en_US
# es_ES fr_FR it_IT pl_PL pt_PT

CODE-a2blib=a2blib

all: pofiles 

# binaries 

# all: all-css

test:
	@echo Src domains: $(SRC_DOMAINS:%=common/locale/%.pot)

messages: $(SRC_DOMAINS:%=%/locale/%.pot)

define DOMAIN_template
l10n/$(1).files: FORCE
	@find $$(CODE-$(1)) -name '*.php' > $$@.tmp
	@find $$(CODE-$(1)) -name '*.inc' >> $$@.tmp
	@if [ -f $$@ ] && diff -q $$@ $$@.tmp > /dev/null ; then \
		rm -f $$@.tmp ; \
		else mv -f $$@.tmp $$@ ; \
		fi

l10n/$(1).pot: l10n/$(1).files
	@xgettext --omit-header -o $$@ -L PHP -f $$<
endef

define UI_template
l10n/$(2)/$(1).po: l10n/$(1).pot
	if [ ! -f $$@ ] ; then \
		msginit --no-translator -o $$@ -i $$< -l $(2) ; \
	else msgmerge -U $$@ $$< ; fi

$(CODE-$(1))/locale/$(2)/LC_MESSAGES/$(1).mo: l10n/$(2)/$(1).po
	@if [ ! -d $(CODE-$(1))/locale/$(2)/LC_MESSAGES/ ] ; then \
		mkdir -p $(CODE-$(1))/locale/$(2)/LC_MESSAGES/ ; fi
	msgcat --use-first $$^ | msgfmt -o $$@ '-'
	
pofiles: l10n/$(2)/$(1).po
binaries: $$(CODE-$(1))/locale/$(2)/LC_MESSAGES/$(1).mo
endef

# $(foreach clang,$(LANGS-common),$(eval $(call COMMON_template,$(clang))))
$(foreach uii,$(SRC_DOMAINS),$(eval $(call DOMAIN_template,$(uii))))
$(foreach uii,$(SRC_DOMAINS),$(foreach lang,$(LANGS-$(uii)),$(eval $(call UI_template,$(uii),$(lang)))))

gettexts:
	@echo "Gettext compilation finished, you can transfer them to your web server now."
	@echo
	@echo "Please note that you may need to *restart* the httpd to let new texts appear"

#  A template for a specific domain/style sheet.
#  Args: $1: domain, $2 style
define CSS2_template

# $$(CODE-$(1))/css/$(2).css: common/css-src/common/$(2)/ common/css-src/$(1)/$(2)/

$$(CODE-$(1))/css/$(2).css: common/css-src/common/$(2)/*.inc.css common/css-src/$(1)/$(2)/*.inc.css
	cat $$^ > $$@

clear-css: $$(CODE-$(1))/css/$(2).css

endef

define CSS_template
STYLES-$(1)-name:=$$(subst common/css-src/$(1)/,,$$(wildcard common/css-src/$(1)/*))
STYLES-$(1)-files:=$$(foreach name,$$(STYLES-$(1)-name),$$(CODE-$(1))/css/$$(name).css)

css-$(1): $$(CODE-$(1))/css $$(STYLES-$(1)-files)

all-css: css-$(1)

$$(CODE-$(1))/css:
	@[ ! -f $$(CODE-$(1))/css ] || ( echo "$$(CODE-$(1))/css is a file!" ; exit 1 )
	@mkdir -p $$(CODE-$(1))/css/
	@cd $$(CODE-$(1))/css/ ; ln -s ../../common/css/images ./

$$(foreach style,$$(STYLES-$(1)-name),$$(eval $$(call CSS2_template,$(1),$$(style))))

endef

$(foreach uii,$(DST_DOMAINS),$(eval $(call CSS_template,$(uii))))

clear-css:
	rm -f $^

list-css:
	echo $(STYLES-admin-name)
	#echo $(STYLES-admin)

FORCE: ;
.SILENT: messages test %/locale/%.pot
#eof
