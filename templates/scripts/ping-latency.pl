$host = $ARGV[0];
#$rrd = $ARGV[1];


open (PING, "/bin/ping -nqc 20 -i .5 -w 5 $host|");
while (<PING>) {
/(\d+)% packet loss/ && ($loss = $1);
/= (.+)\/(.+)\/(.+)\/(.+) ms/ && (($min,$avg,$max,$dev) = ($1,$2,$3,$4));
};
close (PING);

print "min:$min avg:$avg max:$max dev:$dev loss:$loss";
