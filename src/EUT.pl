#!/usr/bin/perl

use strict;
use warnings;
use File::Basename;

my $inD = shift;
my $outD = shift;

if (!defined($inD) || !defined($outD))
{
	die "$0 <Input directory> <Output file path>\n";
}

system("mkdir -p $outD") unless -d $outD;

## Required parameters
## Hash variables
my %hash_adjS = ();
my %hash_data = ();


my @arr_in = <$inD/*>;

foreach my $inP (@arr_in)
{
	open(INP,$inP);

	while(<INP>)
	{
		chomp;
		if ($_ =~/^insert into FEATURE_DENSITY values \((.+)\)/)
		{
			my @arr_adjS = split(/,/,$1);
			for (my $i = 0; $i <= $#arr_adjS; $i++)
			{
				if ($arr_adjS[$i] =~ /'(.+)'/)
				{
					$arr_adjS[$i] = $1;
				}
			}
			my $eut = $arr_adjS[1];
			my $chr = $arr_adjS[2];
			my $pos = $arr_adjS[4];
			my $score = $arr_adjS[6];
			if (!exists($hash_adjS{$chr}))
			{
				$hash_adjS{$chr} = "$eut\t$chr\t$pos\t$score";
			}
			else
			{
				$hash_adjS{$chr} .= "\n$eut\t$chr\t$pos\t$score";
			}
		}
		if ($_ =~ /^insert into CONSENSUS values\((.+)\)/)
		{
			my @arr_data = split(/,/,$1);
			for (my $i = 0; $i <= $#arr_data; $i++)
			{
				if ($arr_data[$i] =~ /'(.+)'/)
				{
					$arr_data[$i] = $1;
				}
			}
			my $rSpc = $arr_data[0];
			my $rChr = $arr_data[1];
			my $rS = $arr_data[2];
			my $rE = $arr_data[3];
			my $dir = $arr_data[6];
			my $tSpc = $arr_data[7];
			my $tChr = $arr_data[8];
			if (!exists($hash_data{$rChr}{$tSpc}))
			{
				$hash_data{$rChr}{$tSpc} = "$rSpc\t$rChr\t$rS\t$rE\t$dir\t$tSpc\t$tChr";
			}
			else
			{
				$hash_data{$rChr}{$tSpc} .= "\n$rSpc\t$rChr\t$rS\t$rE\t$dir\t$tSpc\t$tChr";
			}
		}
	}

	close(INP);
}

foreach my $rChr (keys %hash_data)
{
	if (exists($hash_adjS{$rChr}))
	{
		open(W,">$outD/EUT.$rChr.adjS.txt");
		print W "$hash_adjS{$rChr}\n";
		close(W);
	}
	open(R,">$outD/EUT.$rChr.info.txt");
	foreach my $tSpc (keys %{$hash_data{$rChr}})
	{
		print R "$hash_data{$rChr}{$tSpc}\n";
	}
	close(R);
}
