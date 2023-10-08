#!/usr/bin/perl

use strict;
use Data::Dumper;

my $debug = 1;
my $ip = $ARGV[0];
my $action1 = $ARGV[1];
my $action2 = $ARGV[2];
my $whichport = $ARGV[3];

open (LOG, ">>/tmp/voice-port-usage.txt") if ($debug >= 1);
print LOG "voice-port-usage.pl $ip $action1 $action2 $whichport\n" if ($debug >=1);


# Configure these to match your rancid installation, or replace with some other login method
my $clogin = "";
my $cloginrc = "";


if (!$clogin || !$cloginrc) {
  die 'Please edit this script to configure $clogin and $cloginrc to match your rancid installation.';
}

my $command = "show voice port summary; show interfaces description";

print LOG "Running: $clogin -f $cloginrc -c '$command' $ip\n" if ($debug >= 2);
open PORTS, "$clogin -f $cloginrc -c '$command' $ip|";

my $founddata = 0;
my $datadone = 0;
my $foundints = 0;
my $done = 0;
my %ports;
while (my $line = <PORTS>) {
    print LOG "Read: $line" if ($debug >= 2);
    if (!$founddata) {
	if ($line =~ /===/) {
	    $founddata = 1;
	}
    } elsif (!$datadone) {
	if ($line =~ /^\s*$/) {
	    $datadone = 1;
	    next;
	}

	my ($port, $ch, $type, $admin, $oper, $instat, $outstat, $ec) = split(/\s+/, $line);
	next if ($type eq 'efxs');
	$port =~ s/:\d+$//;
	$ports{$port}{$ch}{oper} = $oper;

    } elsif (!$foundints) {
	if ($line =~ /Description/) {
	    $foundints = 1;
	    next;
	}
    } else {
	if ($line =~ /exit/) {
	    $done++;
	    next;
	}

	my ($port, $status, $protocol, $description) = split(/\s{2,}/, $line, 4);
	$port =~ s/:\d+$//;
	$port =~ s/^Se//;
	chomp $description;
	$ports{$port}{description} = $description if ($description  && exists $ports{$port});
    }
}
close PORTS;

if ($action1 eq 'index') {
    foreach my $port (sort keys %ports) {
	print "$port\n";
    }
    exit;
} 

if ($action1 eq 'query' && $action2 eq 'ports') {
    foreach my $port (sort keys %ports) {
	print "$port:$port\n";
    }
    exit;
} 

if ($action1 eq 'query' && $action2 eq 'description') {
    foreach my $port (sort keys %ports) {
	print "$port:$ports{$port}{description}\n";
    }
    exit;
}

if ($action1 eq 'get' && $action2 eq 'description') {
    my $port = $whichport;
    print $ports{$port}{description};
    exit;
}

foreach my $port (sort keys %ports) {
    my $inuse = 0;
    foreach my $chan (sort keys %{$ports{$port}}) {
	next if ($chan eq 'description');
	print "$port $chan $ports{$port}{$chan}{oper}\n" if ($debug >= 2);
	$inuse++ if ($ports{$port}{$chan}{oper} eq 'up');
    }
    $ports{$port}{inuse} = $inuse;
    print LOG "$ip $port has $inuse channels in use\n" if ($debug >= 1);
    
}

if ($action1 eq 'query' && $action2 eq 'inuse') {
    foreach my $port (sort keys %ports) {
	print "$port:$ports{$port}{inuse}\n";
    }
    exit;
}

if ($action1 eq 'get' && $action2 eq 'inuse') {
    my $port = $whichport;
    print $ports{$port}{inuse};
    exit;
}

END {
    close (LOG) if ($debug >= 1);
}
