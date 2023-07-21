################################################################################
# build.spec
################################################################################

#
# notes:
#
#  If the first argument to %pre and %post is 1, the action is an initial installation.
#  If the first argument to %pre and %post is 2, the action is an upgrade
#
#  %pre and %post aren't executed during an uninstallation
#  If the first argument to %preun and %postun is 1, the action is an upgrade.
#  If the first argument to %preun and %postun is 0, the action is uninstallation.
#

# define the target os so that I can build on my Mac and install on RedHat or CentOS
%define _target_os linux


# define the installation directory
%define toplevel_dir /var/www/html
%define install_target %{toplevel_dir}/%{name}

# doc_root will get a pointer to the install target 
%define doc_root /var/www/html

# cron files get installed here
%define cron_dir /etc/cron.d

# enable cron jobs on what host?
#%define enable_cron_jobs dev
#%define enable_cron_jobs qa
%define enable_cron_jobs prod
#%define enable_cron_jobs none


################################################################################
# main definitions here
Summary:   BladeRunner - All your blade are belong to us
Name:      %{name}
Version:   %{version}
Release:   %{release}
#BuildRoot: %{_topdir}/buildroot
%define buildroot %{_topdir}/buildroot
BuildArch: noarch
License:   Neustar/Restricted
Group:     Neustar/AT
Requires:  sts-lib

# RPM_BUILD_DIR = pkg/rpmbuild/BUILD
# RPM_BUILD_ROOT = pkg/rpmbuild/buildroot

################################################################################
%description
BladeRunner - All your blade are belong to us
Queries VMware vSphere to obtain data centers, clusters, ESX blades and VMs
Save the data in a serialized JSON file in the data directory to be used
in reports


################################################################################
%prep


################################################################################
%install

export RPM_BUILD_DIR=`pwd`

# create build root
mkdir -p $RPM_BUILD_ROOT/%{install_target}

# copy files to build root
cp -R * $RPM_BUILD_ROOT/%{install_target}

# Tag the release and version info into the ABOUT file
echo 'BladeRunner Version %{version}-%{release}, Built %{release_name}' > $RPM_BUILD_ROOT/%{install_target}/ABOUT

# install the cron jobs
install -m 755 -d $RPM_BUILD_ROOT/%{cron_dir}
install -m 644 $RPM_BUILD_DIR/%{cron_dir}/br_get_esxhypervisors $RPM_BUILD_ROOT/%{cron_dir}/br_get_esxhypervisors

# create the other directories
install -m 755 -d $RPM_BUILD_ROOT/%{toplevel_dir}/%{name}/log
install -m 755 -d $RPM_BUILD_ROOT/%{toplevel_dir}/%{name}/data
install -m 755 -d $RPM_BUILD_ROOT/%{toplevel_dir}/%{name}/export


################################################################################
%clean
rm -rf $RPM_BUILD_ROOT


################################################################################
%pre
if [ -n "$1" ]; then
    if [ $1 -eq 1 ]; then
        # initial install action here
        echo "Executing pre install actions"
    elif [ $1 -eq 2 ]; then
        # upgrade action here
        echo "Executing pre upgrade actions"
    fi
fi


################################################################################
%post
if [ -n "$1" ]; then
    if [ $1 -eq 1 ]; then
        # initial install action here
        echo "Executing post install actions"
    elif [ $1 -eq 2 ]; then
        # upgrade action here
        echo "Executing post upgrade actions"
    fi
fi

# create a sym link to /opt
if [ ! -h %{doc_root}/%{name} ]; then
    ln -s %{install_target} %{doc_root}
fi

# change file permissions
chown -R stsapps.stsapps %{install_target}/log %{install_target}/data

# enable the cron jobs
hostname=`/bin/hostname -s`
update=0
if [ %{enable_cron_jobs} == "dev" -a $hostname == "statvdvweb01" ]; then
    update=1
elif [ %{enable_cron_jobs} == "qa" -a $hostname == "statvqaweb01" ]; then
    update=1
elif [ %{enable_cron_jobs} == "prod" -a $hostname == "statvbr01" ]; then
    update=1
fi

if [ $update -eq 1 ]; then
    echo "Updating the config file to enable cron jobs..."
    cp %{install_target}/config/config.php %{install_target}/config/config.php.rpm
    cat %{install_target}/config/config.php | sed -r 's/([ \t]*"runCronJobs"[ \t]*=> )false,/\1true,/' >/tmp/config.php
    install -m 644 /tmp/config.php %{install_target}/config/config.php
    rm -f /tmp/config.php %{install_target}/config/t
fi

# clean up unnecessary files
if [ -e %{install_target}/build.xml ]; then
    rm -f %{install_target}/build.xml
fi

if [ -e %{install_target}/%{name}.spec ]; then
    rm -f %{install_target}/%{name}.spec
fi

# correct permissions
find %{install_target} -type d -exec chmod 755 {} \;
find %{install_target} -type f -exec chmod 644 {} \;
find %{install_target}/bin -type f -exec chmod 755 {} \;

################################################################################
%verifyscript


################################################################################
%preun

# do we have a non-empty param 1?
if [ -n "$1" ]; then
    # yep, check the value
    if [ $1 -eq 0 ]; then
        # uninstall action here
        echo "Executing preun uninstall actions"
    elif [ $1 -eq 1 ]; then
        # upgrade action here
        echo "Executing preun upgrade actions"
    fi
fi


################################################################################
%postun
if [ -n "$1" ]; then
    if [ $1 -eq 0 ]; then
        # uninstall action here
        echo "Executing postun uninstall actions"
    elif [ $1 -eq 1 ]; then
        # upgrade action here
        echo "Executing postun upgrade actions"
    fi
fi


################################################################################
%files

%defattr(-,root,root,-)

%{toplevel_dir}
%{cron_dir}/*

%attr(755,root,root) %{install_target}/bin
%attr(755,root,root) %{install_target}/bin/get_esxhypervisors
%attr(644,root,root) %{install_target}/resources/sounds/*

%dir %attr(755,stsapps,stsapps) %{install_target}/log
%dir %attr(755,stsapps,stsapps) %{install_target}/data
%dir %attr(755,apache,apache) %{install_target}/export
