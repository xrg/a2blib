%define git_repo a2blib
%define git_head HEAD
%define version %git_get_ver
%define distsuffix xrg

Summary:	A2B framework for PHP
Name:		php-a2blib
Version:	%{version}
Release:	%mkrel %git_get_rel
License:	LGPL
Group:		Development/PHP
Source0:	%git_bs_source %{name}-%{version}.tar.gz
BuildArch:	noarch
BuildRequires:	gettext
Requires:	postgresql >= 8.2.5
Requires:	php-pgsql
Requires:	php-gettext
Requires:	php-gd
%if %{_target_vendor} == redhat
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-buildroot
%endif

%description
MVC framework for PHP, taken from the A2Billing v200 project.

%prep
%git_get_source
%setup -q

%build


%install
[ "%{buildroot}" != "/" ] && rm -rf %{buildroot}

install -d %{buildroot}%{_datadir}/php/
cp -aR a2blib %{buildroot}%{_datadir}/php/

%clean
[ "%{buildroot}" != "/" ] && rm -rf %{buildroot}

%files
%defattr(-,root,root)
%{_datadir}/php/a2blib

