COMPONENT_NAME:=plugin-load-test
COMPONENT:=orba/plugin-load-test
SKELETON:=git@bitbucket.org:orbainternalprojects/skeleton.git

TARGET_PATH:=source/packages/$(COMPONENT_NAME)

MKTEMP:=mktemp -d
TMPDIR:=$(shell $(MKTEMP))
ARCHIVE:=$(TMPDIR)/archive.tar.gz
CP:=cp -R
RM:=rm -rf
TAR:=tar
GIT:=git
MKDIR=mkdir -p

#@todo handle windows

.PHONY: all

all:
	$(info Running)
	$(info $(TMPDIR))

	$(TAR) -czvf $(ARCHIVE) .
	$(RM) ..?* .[!.]* *
	$(GIT) clone $(SKELETON) .
	$(MAKE) new \
		project=$(COMPONENT_NAME) \
		version=2.4.3-p1 \
		edition=community \
		static_cases=packages/$(COMPONENT_NAME)
	$(MKDIR) $(TARGET_PATH)
	$(TAR) -xzvf $(ARCHIVE) -C $(TARGET_PATH)
	$(RM) $(TMPDIR)
	$(MAKE) run cmd="composer\ require\ $(COMPONENT)"
