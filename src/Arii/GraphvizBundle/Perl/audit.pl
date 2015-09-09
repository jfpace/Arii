#!/usr/bin/perl -w
# --------------------------------------------------------------------------------------
# Copyright (C) 2012 SOS Paris SAS
# Author: Eric Angenault
# Mail:   eric.angenault@sos-paris.com
#
# GRAPHVIZ for Open Source Job Scheduler
#
# $Id$
# --------------------------------------------------------------------------------------

#use strict;
use Getopt::Long;
use XML::Simple;
use Data::Dumper;
use Encode;
use File::Basename;

my ($mainfolder,$web,$tmp,$dot,$img,$config, $events, $show_params, $show_events, $show_def, $show_doc, $show_time, $show_chains, $show_jobs, $show_config, $status, $scheduler_xml, $images, $path, $file, $event_file, $debug, $lang, $code, $target, $splines, $rankdir, $help) = 
   ('live','/','.','dot','img','.', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'y', '', '', 'images', '.*',  '.*',  '.*', '', 'fr', 'n', '', 'polyline', 'TB'  );

# check command line arguments
usage() if ( ! GetOptions(	
	'help|h'     => \$help,
	'folder=s' => \$mainfolder,
        'web=s'     => \$web,
	'dot=s'     => \$dot,
	'tmp=s'     => \$tmp,
	'img=s'     => \$img,
	'config=s'     => \$config,
	'events=s',     => \$events,
	'show_params=s',     => \$show_params,
	'show_events=s',     => \$show_events,
	'show_jobs=s',     => \$show_jobs,
	'show_chains=s',     => \$show_chains,
	'show_config=s',     => \$show_config,
	'show_doc=s',     => \$show_doc,
	'scheduler_xml=s',     => \$scheduler_xml,
	'images=s'		=> \$images,
	'code=s'		=> \$code,
	'path=s'		=> \$path,
	'file=s'		=> \$file,
	'event_filter=s'	=> \$event_file,
	'target'		=> \$target,
	'splines=s'	=> \$splines,				
	'rankdir=s'	=> \$rankdir,				
	'status=s'	=> \$status,				
	'debug=s'		=> \$debug,
	) or defined $help);

usage()
	unless $config;

my(%Img) = ( 
	'action'=>			'eye',
	'absolute_repeat'=>	'timeline_marker',
	'add_event'=>		'brick_add',
	'add_order'=>		'lightning_add',
	'begin'=>			'control_start',
	'documentation'=>	'book',
	'doc_live'=>		'book_go',
	'doc_file'=>		'book_add',
	'bug'=>				'bug',
	'calendar'=>		'calendar',
	'chain'=>			'chart_organisation',
        'comment'=>             'comment',
	'config'=>			'wrench',
	'directory_change'=>'page_lightning',
	'day'=>				'calendar_view_day',
	'date'=>			'date_add',
	'delay'=>			'clock_stop',
	'distributed'=>		'arrow_join',
	'end'=>				'control_stop',
	'end_node'=>		'control_end',
	'event'=>			'arrow_inout',
	'event_name'=>		'brick',
	'event_class'=>		'briefcase',
	'event_id'=>		'anchor',
	'event_title'=>		'comment',
	'event_group'=>		'bricks',
	'error'=>			'error',
	'exit_code'=>		'door_open',
	'group'=>			'text_list_bullets',
	'holiday'=>			'date_delete',
	'holiweekdays'=>	'calendar_delete',	
	'include'=>			'table_row_insert',
	'job_chain'=> 		'script_code',
	'job_name'=>		'script_go',
	'java_class'=> 		'script_gear',
	'language'=>		'cog',
	'lock'=>			'lock',
	'lock_open'=>		'lock_open',
	'max_orders'=>		'bullet_arrow_top',
	'mail'=>			'email',
	'mail_on'=>			'email_go',	
	'mail_on_error'=>	'delete',
	'mail_on_success'=>	'accept',
	'mail_on_warning'=>	'error',
	'mail_on_process'=>	'cog',
	'monitor'=>			'plugin',
	'month'=>			'calendar_view_month',
	'move_to'=>			'page_white_go',
	'on_error'=>		'error',
	'port'=>			'server_connect',
	'process'=>			'script_gear',
	'process_class'=>	'flag_green',
	'directory'=>		'folder',
	'order'=>			'lightning',
	'ordered'=>			'link',
	'param'=>			'bullet_yellow',
	'regex'=>			'page_white',
	'remote_scheduler'=>'computer',
	'remove_event'=>	'brick_delete',
	'visible'=>			'eye',
	'let_run'=>			'hourglass_go',
	'line'=>			'bullet_black',
	'log_mail_cc'=>		'email_add',
	'max_processes'=>	'application_cascade',
	'order_chain'=>		'link_go',
	'orders_recoverable'=>'database',
	'remove'=>			'page_white_delete',
	'repeat'=>			'control_repeat',
	'schedule'=>		'calendar',
	'schedule_no'=>		'calendar_delete',
	'script'=>			'script_code',
	'single_start'=>	'clock',
	'substitute'=>		'calendar_delete',
	'content'=>			'application_osx_terminal',
	'setback'=>			'pill',	
	'shell'=>			'application_osx_terminal',
	'spooler_id'=>		'server',	
	'standalone'=>		'link_break',
	'start_job'=>		'link_go',
	'state'=> 			'sitemap',
	'stop'=>			'stop',
	'stop_on_error'=>	'stop',
	'supervisor'=>		'eye',
	'title'=>			'comment',
	'valid_from'=>		'calendar_add',
	'valid_to'=>		'calendar_delete',
	'weekday'=>			'calendar_view_week',
	'when_holiday'=>	'date_error'
	);

my($imgpath) = $images.'/';
my(%BGColor) = ( 
    'standalone' => '#ffffb3',
    'ordered'    => '#bebada',
    'events'     => '#ccebc5'
 );
my $simulate=1;

my($unicID); # Pour les liens globaux

# Parametres
if ($code =~ /^n/) {
	$code = 'n';
}

my($dir)=0;  # Repertoires
my(@LINKS);  # Tous les liens
my(@JOBS);   # Pour les start_job
my(%DefJobs);# Pour limiter les 

# Tous les evenements
my(%Events); 
my(%StartJob);
my(%EventRule);
my(%ShowEvent);

my $hotfolder="$config/".$mainfolder;
my $rootfolder = $mainfolder;
if ($mainfolder eq 'remote') {
	if ($path =~ s/(.*?)\//\//) {
		$rootfolder = $1;
		$hotfolder .= "/$rootfolder";
	}		
}

print '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
  <title>'.$mainfolder.'</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.min.css">
</head>
<body>
<div class="container-fluid">
<div class="jumbotron">
  <h1>'.$rootfolder.'</h1>
<!--  <p>...</p>
  <p><a class="btn btn-primary btn-lg" href="#" role="button">Learn more</a></p>
-->
</div>
<h1>Analyse des objets</h1>
';
my %Comment;
open(FILE,"$img/comments.txt");
while(<FILE>) {
	chomp;
	my($file,$t,$texte) = split("\t");
	$Comment{$file} = "$t\t$texte";
	#print STDERR "(($file))".$Comment{$file}."\n";
}
close(FILE);
Folder($hotfolder,$path);
print '</div>
</body>
</html>';

# Recursif
sub Folder {
my($hotfolder,$folder) = @_;
	print "<h2>$folder</h2>\n";
	print STDERR "$folder\n";
	AuditComment($Comment{$folder}) if ($Comment{$folder});
	print "<blockquote>";
	GetFolder($hotfolder,$folder);
	print "</blockquote>";
	opendir(DIR,"$hotfolder/$folder") || die("'$hotfolder/$folder' !");
	my @Dirs=sort readdir(DIR);
	closedir(DIR);
 	foreach my $d (@Dirs) {
		next if ($d eq '.') or ($d eq '..');
		if (-d "$hotfolder/$folder/$d") {
			Folder($hotfolder,"$folder/$d");
		}
	}
} 

sub GetFolder {
my($hotfolder,$folder)=@_;
@LINKS=();
	my $foldernode;
        my $foldername = CleanLabel($folder);

my $schema = $foldername;
$schema =~ s/[\/#]/__/g; 
print "<img src=\"$web/$schema.png\"/>\n";
open(GVZ,"> $tmp/$schema.gvz");
print GVZ "digraph OSJS {\n";
print GVZ "fontname=arial\n";
print GVZ "fontsize=8\n";
print GVZ "splines=$splines\n";
print GVZ "randkir=$rankdir\n";
print GVZ "node [shape=plaintext,fontname=arial,fontsize=8]\n";
print GVZ "edge [shape=plaintext,fontname=arial,fontsize=8,decorate=true,compound=true]\n";
print GVZ "bgcolor=transparent\n";

	if (opendir(DIR, $hotfolder.'/'.$folder)) {
		my @FILES = sort readdir(DIR);
		closedir(DIR);
		foreach my $f (@FILES) {
			my($thisfile) = $folder.'/'.$f;
			if (-d $hotfolder.'/'.$folder.'/'.$f) {
			}
			else {
				AuditComment($Comment{$thisfile}) if ($Comment{$thisfile});
				if (($f =~ /\.xml$/) and ($f =~ /$file/)) {
					# On traite les chaines
					if ($f =~ /\.job_chain\.xml$/) {
						ReadChain("$folder/$f");
					}
					# les ordres
					elsif ($f =~ /\.order\.xml$/) {
						ReadOrder("$folder/$f");				
					}
					# les planifications
					elsif ($f =~ /\.schedule\.xml$/) {
						ReadSchedule("$folder/$f");				
					}
					# les verrous
					elsif ($f =~ /\.lock\.xml$/) {
						ReadLock("$folder/$f");				
					}
					# les machines
					elsif ($f =~ /\.process_class\.xml$/) {
						ReadProcessClass("$folder/$f");				
					}
					# Si c'est un job, on cr�e tous les types et on rattache aux noeuds
					elsif ($f =~ /\.job\.xml$/) {
						ReadJob("$folder/$f");				
					}
					# configuration
					elsif ($f =~ /\.config\.xml$/) {
						ReadConfig("$folder/$f");				
					}
					else {
						Audit("W","Fichier XML '$f' de type inconnu.");
					}
				}
				else {
					if ($f =~ /\.ERROR$/) {
						Audit("S","$f corrigé !");
					}
					else {
						Audit("W","Fichier '$f' inconnu.");
					}
				}
			}
		}
	}
	# On ajoute les liens
	my $l;
	foreach $l (@LINKS) {
		print GVZ "$l\n";
	}
	# On ferme le graph
	print GVZ "}\n";
	close(GVZ);
	
	# Construction de l'image
	$simulate=0;
	if (!$simulate) {
		my $cmd = "\"$dot\" -T png < \"$tmp/$schema.gvz\" > \"$img/$schema.png\"";	
                #print $cmd;
		my $res = `$cmd`;
		print STDERR "$cmd\n"; 
		print STDERR $res; 
		print STDERR "Graph $? $!\n";
	}
}


# ============================================
# GRAPHS CONTEXTUELS
# ============================================

exit 0
	if ($target eq '');

# --------------------------------------------
# usage()
# --------------------------------------------
sub usage {
   print STDERR "
audit.pl --config=<config>

	-config	R�pertoire de configuration
Options:
	-path	R�pertoire contenant les objets
	-file	Fichiers particuliers � prendre en compte
	-images	R�pertoire des images
	-script	(o|y|n)	Affichage du script 
	-debug	(order|job|chain|config|process|lock) Affichage du xml
";
exit;
}

# ---------------------------------------------
# usage_error()
# ---------------------------------------------
sub usage_error {
   my ($error) = @_;
   print STDERR "\nERROR-001: command line error!\n$error\n";
   usage();
   exit(1);
}

# ---------------------------------------------
# ReadJob()
# ---------------------------------------------
sub ReadJob {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	if ($debug =~ /job/) {
		print STDERR "$hotfolder/$f\n";
		print STDERR Dumper $doc
	}

	$f =~ s/\.job\.xml$//;
	my $jobname = CleanFilename($f);
	
	# Test de conformité
	Audit('E','Job non conforme') unless $doc->{job};
	
    my $jobtype = 'standalone';
	if ($doc->{job}->{order}) {
                $jobtype = 'ordered'
                    if ($doc->{job}->{order} eq 'yes');
	}
        my $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="'.$BGColor{$jobtype}.'">'."\n";
        $label .= TableLine(0, $jobtype, $jobname );

	$label .= TableLine(0, 'title', CheckString ($doc->{job}->{title} ) )
		if ($doc->{job}->{title});

	# Documentation
        if ($show_doc !~ /^n/) {
            if ($doc->{job}->{description}) {
                    if ($doc->{job}->{description}->{content}) {
                            $label .= TableLine(0, 'documentation', $doc->{job}->{description}->{content});
                    }
                    else {
                            $label .= TableLine(0, 'documentation', ' ' );
                    }
                    if ($doc->{job}->{description}->{include}) {
                            if ($doc->{job}->{description}->{include}->{file}) {
                                    $label .= TableLine(1, 'doc_file', $doc->{job}->{description}->{include}->{file});
                            }
                            if ($doc->{job}->{description}->{include}->{live_file}) {
                                    $label .= TableLine(1, 'doc_live', $doc->{job}->{description}->{include}->{live_file});
                            }
                    }

            }
        }

	# Directory change 
	if ($doc->{job}->{start_when_directory_changed}) {
		$label .= TableLine(0, 'directory_change', ' ');
		$label .= TableLine(1, 'directory', $doc->{job}->{start_when_directory_changed}->{directory});
		if ($doc->{job}->{start_when_directory_changed}->{regex}) {
			$label .= TableLine(1, 'regex', $doc->{job}->{start_when_directory_changed}->{regex});
		}
		else {
			$label .= TableLine(1, 'regex', 'N/A' );
		}
	}

	# Planification
        if ($show_time !~ /^n/) {
            if ($doc->{job}->{run_time}) {
                    $label .= GetSchedule($doc->{job}->{run_time},$jobname);
            }
        }

	# Settings
	if ($doc->{job}->{settings}) {
		my $n=0;
		foreach my $p ('mail_on_error', 'mail_on_warning','mail_on_success','mail_on_process') { $n++; };
		my $to = ' ';
		if ($doc->{job}->{settings}->{log_mail_to}) {
			$to = $doc->{job}->{settings}->{log_mail_to};
		}
		$label .= TableLine(0, 'mail', $to )
			if ($n > 0);
		if ($doc->{job}->{settings}->{log_mail_cc}) {
			$label .= TableLine(1, 'log_mail_cc', $doc->{job}->{settings}->{log_mail_cc} );
		}
		if ($n > 0) {
			my $mail_on = '';
			foreach my $p ('mail_on_error', 'mail_on_warning','mail_on_success','mail_on_process') {
				if ($doc->{job}->{settings}->{$p}) {
					if ($doc->{job}->{settings}->{$p} eq 'yes') {
						$mail_on .= ' '.$p; 
					}
				}
			}
			$mail_on =~ s/mail_on_//g;
			if ($mail_on ne '') {
				$label .= TableLine(1, 'mail_on', $mail_on );
			}
		}
	}

	if ($doc->{job}->{script}) {
		if ($doc->{job}->{script}->{language}) {
			if ($doc->{job}->{script}->{language} eq 'shell') {
				$label .= TableLine(0, 'shell', $doc->{job}->{script}->{language});
			}
			else {
				$label .= TableLine(0, 'language', $doc->{job}->{script}->{language});
			}
		}
		if ($doc->{job}->{script}->{include}) {
			$label .= TableLine(0, 'include', $doc->{job}->{script}->{include});
			# Ouverture du script ?
			GetInclude($doc->{job}->{script}->{include},Basename($f));
		}
		if (($code ne 'n') and ($doc->{job}->{script}->{content})) {
			my ($begin,$end) = (0,0);
			my @CODE = split("\n", $doc->{job}->{script}->{content} );
			$begin = 0;
			while (trim($CODE[$begin]) eq '') { $begin++; };
			$end = $#CODE;
			while (trim($CODE[$end]) eq '')  { $end--; };
			my $line;
			for(my $i=$begin;$i<$end;$i++) {
				$line = $CODE[$i];
	# A encoder
	#			$label .= TableLine(1, $i+1-$begin, $line );
			}
		}
	}
	
	# Process
	if ($doc->{job}->{process}) {
		$label .= TableLine(0, 'process', $doc->{job}->{process}->{file} );
		if ($doc->{job}->{process}->{param}) {
			$label .= TableLine(1, 'param', $doc->{job}->{process}->{param} );
		}
	}
	
	# Script
	foreach my $s ('java_class','shell') {
		if ($doc->{job}->{script}->{$s}) {
			$label .= TableLine(0, $s, $doc->{job}->{script}->{$s} );
		}
	}
	if (($code ne 'n') and ($doc->{job}->{script}->{content}) ) {
		my $content =  $doc->{job}->{script}->{content};
		$content =~ s/\r//;
		foreach my $c (split("\n",$content)) {
			$c =~ s/\s+$//;
			$label .= TableLine(1, 'line', $c )."\n"
				if ($c ne '');
		}
	}

	# Parametres
	if ($doc->{job}->{params}) {
		$label .= GetParams($doc->{job}->{params});

                # Test sur evenement
                if ($doc->{job}->{params}->{param}->{scheduler_event_class}) {
                    my $event = $doc->{job}->{params}->{param};
                    # On retrouve l'evenement de la règle
                    my $class = $event->{scheduler_event_class}->{value};
                    # ID multiples (modif Klaus)
                    if ($event->{scheduler_event_id}) {
                        foreach my $id (split(';',$event->{scheduler_event_id}->{value})) {
                            # On recherche la classe et l'id
                            if ($EventRule{"$class|$id"}) {
                                my $action = $EventRule{"$class|$id"};
                                # pour le positionner
                                print GVZ '"EVENT//'.$action.'"'."\n";
                                push(@LINKS, '"'.$jobname.'" -> "EVENT//'.$action.'" [style=dashed,color=black]');
                                $ShowEvent{$action}++;
                            }
                        }
                    }
                }
	}
		
	if ($doc->{job}->{process_class}) {
		$label .= TableLine(0, 'process_class', $doc->{job}->{process_class})."\n";
		# Un lien de la definition vers le job
		push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "MAC//'.$doc->{job}->{process_class}.'"  [color=cyan,style=dotted]');
	}

	if ($doc->{job}->{stop_on_error}) {
		if ($doc->{job}->{stop_on_error} eq 'yes') {
			$label .= TableLine(0, 'stop_on_error', 'Stop');
			Audit('E', 'Un job ordonnné ne devrait pas être stoppé.') if ($jobtype eq 'ordered');
		}
	}
	else {
		# $label .= TableLine(0, 'stop_on_error', 'Stop');
	}
	
	# Setback
	if ($doc->{job}->{delay_order_after_setback}) {
		my @SETBACKS = GetRefArray( $doc->{job}->{delay_order_after_setback} );
		$label .= TableLine(0, 'setback', 'setback' );
		foreach my $setback ( @SETBACKS ) {
			if ( $setback->{is_maximum}) {
				if ( $setback->{is_maximum} eq 'yes' ) {
					$label .= TableLine(1,  $setback->{setback_count}, 'IMG/stop' );
				}
				else {
					$label .= TableLine(1, $setback->{setback_count}, $setback->{delay} );
				}
			}
		}
	}
	
	# Errors
	if ($doc->{job}->{delay_after_error}) {
		my @ERRORS = GetRefArray( $doc->{job}->{delay_after_error});
		$label .= TableLine(0, 'error', 'error' );
		foreach my $error ( @ERRORS ) {
			if ( $error->{delay} ) {		
				if ( $error->{delay} eq 'stop' ) {
					$label .= TableLine(1,  $error->{error_count}, 'IMG/stop' );
				}
				else {
					$label .= TableLine(1,  $error->{error_count}, $error->{delay} );
				}
			}
		}
	}
	
	# Monitor
	if ($doc->{job}->{monitor}) {
		my @MONITORS = GetRefArray( $doc->{job}->{monitor} );
		foreach my $monitor ( @MONITORS ) {
			$label .= TableLine(0,  'monitor', $monitor->{name} )
				if $monitor->{name};
			if (  $monitor->{script} ) {
				$label .= TableLine(1,  'language', $monitor->{script}->{language} );
				$label .= TableLine(1,  'java_class', $monitor->{script}->{java_class} )
					if ( $monitor->{script}->{java_class});
				if ($monitor->{script}->{include}->{live_file}) {
					$label .= TableLine(1,  'include', $monitor->{script}->{include}->{live_file})."\n";
					# On ajoute un lien pour indiquer l'inclusion
					push( @LINKS,  '"'.$jobname.'":'.$unicID.' -> "INCLUDE/'.Basename($f).'/'.$monitor->{script}->{include}->{live_file}.'" [dir=back,color=grey]');				
					# Ouverture du script ?
					GetInclude($monitor->{script}->{include}->{live_file},Basename($f));
				}
			}
		}
	}

	# Verrous
	if ($doc->{job}->{'lock.use'}) {
		my @LOCKS = GetRefArray( $doc->{job}->{'lock.use'} );
		foreach my $lock ( @LOCKS ) {
			if (($lock->{exclusive}) and  ($lock->{exclusive}eq 'yes')) {
				$label .= TableLine(0,  'lock', $lock->{lock} );
				push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "LOCK//'.CleanFilename(Basename( $f ).'/'.$lock->{lock}).'" [color=yellow,style=dashed]' );
			}
			else {
				$label .= TableLine(0,  'lock_open', $lock->{lock} );		
				push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "LOCK//'.CleanFilename(Basename( $f ).'/'.$lock->{lock}).'" [color=yellow,style=dotted]' );
			}
		}
	}

	# On traite la s�rie de commandes
	if ($doc->{job}->{commands}) {
		my @COMMANDS = GetRefArray( $doc->{job}->{commands} );
		foreach my $command ( @COMMANDS ) {
			my $type = $command->{on_exit_code};
			if ($command->{start_job}) {
				my @STARTS = GetRefArray( $command->{start_job} );
				foreach my $start ( @STARTS ) {
					if ($start->{job}) {
						my $next = $start->{job};
						if (my $at = $start->{at}) {
							$label .= TableLine(0,  'start_job', $next, $at);	
						}
						else {
							$label .= TableLine(0,  'start_job', $next);	
						}
						# Bidouille a verifier
						$next =~ s/^\///;
						if ($type eq 'success') {
							push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'" [color=green]'); 
						}
						elsif ($type eq 'error') {
							push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'" [color=red]'); 
						}
						elsif ($type =~ /^SIG/) {
							push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'" [label='.$type.',color=purple]'); 
						}
						else {
							push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'" [label='.$type.',color=orange]'); 
						}
						$label .= GetParams($start->{params});
					}
				}
			}
			if ( $command->{order} ) {
				# Departs de chaines
					#my @ORDERS = ();
                                        my @ORDERS = GetRefArray( $command->{order} );
					#if ( ref ( $command->{order} ) eq 'HASH' ) {
                                            # Ordre direct ou non ?
                                         #   if ($command->{order}->{id}) {
                                         #       push( @ORDERS, $command->{order} );
                                         #   }
                                         #   else {
					#	foreach my $k ( keys %{ $command->{order} } ) {
				#			push( @ORDERS, $command->{order}->{$k} );
					#	}
                                        #    }
					 #}
					#else {
					#	@ORDERS = GetRefArray( $command->{order} );
					#}
					
					foreach my $start ( @ORDERS ) {
						if ($start->{job_chain}) {
							my $next = $start->{job_chain};
							$next =~ s/^\///;
							if (my $at = $start->{at}) {
								$label .= TableLine(0,  'order_chain', $next, $at);	
							}
							else {
								$label .= TableLine(0,  'order_chain', $next);	
							}
							if ($type eq 'success') {
								push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'/HEAD" [color=green]'); 
							}
							elsif ($type eq 'error') {
								push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'/HEAD" [color=red]'); 
							}
							elsif ($type =~ /^SIG/) {
								push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'/HEAD" [label='.$type.',color=purple]'); 
							}
							else {
								push(@LINKS, '"'.$jobname.'":'.$unicID.' -> "'.$next.'/HEAD" [label='.$type.',color=orange]'); 
							}
							$label .= GetParams($start->{params});
						}
					}
			}
		}
	}
	$label .= '</TABLE>';
	print GVZ '"'.$jobname.'" [label=<'.$label.">]\n";		
	push(@JOBS,$jobname);
        
        # Un evenement le pousse ?
        if ($StartJob{$jobname}) {
            my $action = $StartJob{$jobname};
            # pour le positionner
            print GVZ '"EVENT//'.$action.'"'."\n";
            push(@LINKS, '"EVENT//'.$action.'" -> "'.$jobname.'" [style=dashed,color=black]');
            $ShowEvent{$action}++;
        }
	
}

# ---------------------------------------------
# ReadChain()
# ---------------------------------------------
sub ReadChain {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /chain/);

	$f =~ s/\.job_chain\.xml$//;
	my $local = $f; # localisation
	$local =~ s/^\.\///;
	my($chain) = CleanChainLabel($f);
	print GVZ "subgraph \"cluster$f$chain\" {\n";
	print GVZ "style=filled;\n";
	print GVZ "color=lightgrey;\n";
	print GVZ 'label="'.$local."\"\n";

	# Tete de la chaine
	my($chain_node) = CleanFilename("$f/HEAD");

	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
	$label .= TableLine(0, 'chain', $chain );
	foreach my $k ('title','max_orders','orders_recoverable','distributed','visible') {
		if ($doc->{job_chain}->{$k}) {
			$label .= TableLine(0, $k, $doc->{job_chain}->{$k} );
		}
	}
	$label .= '</TABLE>';
	# Audit 
	Audit('W','Il est préférable de sauvegarder les ordres en base de données')
		unless ($doc->{job_chain}->{orders_recoverable} eq 'yes');
	
	# Pour les ordres
	 print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";		
	# A voir a quoi ca sert ?
	#print GVZ '"'.$local.'" [label=<'.$label.">]\n";
	
	# Sinon c'est un file order source 
	if ($doc->{job_chain}->{file_order_source}) {
		$chain_node = CleanFilename("$f/SOURCE");
		my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
		$label .= TableLine(0,  'directory_change', ' ' );
                # Cas de plusieurs file order source
                my(@SOURCES) = GetRefArray( $doc->{job_chain}->{file_order_source} ); 
		my($source);
		foreach $source ( @SOURCES ) {
                    $label .= TableLine(1,  'directory', $source->{directory} );                    
                    $label .= TableLine(1,  'regex', $source->{regex} );
                }
		$label .= '</TABLE>';
		print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";		
	}

	# Liste des noeuds
	if ($doc->{job_chain}->{job_chain_node}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{job_chain_node} ); 
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print GVZ "\"$chain_node\"\n";

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";

			# Etape courante 
			if ( $node->{job} ) {
				if ($node->{state}) {
					$label .= TableLine(0,  'state', $node->{state} );
				}
				else {
					$label .= TableLine(0,  'state', 'N/A' );
				}
			}
			# Sinon, c'est un job de fin
			else {
				$label .= TableLine(0,  'end_node', $node->{state} );
			}

			# Pause
			if ($node->{delay}) {
				$label .= TableLine(0,  'delay', $node->{delay} );		
			}
			
			# En cas d'erreur
			if ($node->{on_error}) {
				if ($node->{on_error} eq 'suspend') {
					print GVZ '"'.$chain_node.'" -> "'.$chain_node.'" [color=red,dir=back]'."\n";					
				}
				elsif ($node->{on_error} eq 'setback') {
					print GVZ '"'.$chain_node.'" -> "'.$chain_node.'" [color=red,style=dashed,dir=back]'."\n";					
				}
			#	$label .= TableLine(0,  'on_error', $node->{on_error} );
			}

			# Prochaines etapes
			my($next);
			if ($node->{next_state}) {
				$next = $node->{next_state};
				print GVZ '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=green,style=dashed]'."\n";
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				print GVZ '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=red,style=dashed]'."\n";
				Audit('I', 'Pour remonter une erreur au niveau du JID, il faut que l\'étape contienne "ERROR" ou qu\'elle commence par un point d\'exclamation. Etape ('.$node->{error_state}.')')
					unless (($node->{error_state} =~ /ERROR/) or ($node->{error_state} =~ /^!/));
			}
						
			# Si on a un job, on le lie � l'order
			if ($node->{job}) {
				$label .= TableLine(0,  'job_chain', $node->{job} );
				# On stocke les liens pour �viter qu'il se retrouvent dessin�s dans la chaine
				# Attention au cas du /
				if ($node->{job} =~ s/^\///) {	
					push(@LINKS,'"'.$chain_node.'":'.$unicID.' -> "'.$node->{job}.'" [style=dotted]');
				}
				else {
					push(@LINKS,'"'.$chain_node.'":'.$unicID.' -> "'.CleanFilename(Basename("$f").'/'.$node->{job}).'" [style=dotted]');
				}
			}
			
			$label .= '</TABLE>';
			print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";
			}
	}
	elsif ($doc->{job_chain}->{'job_chain_node.job_chain'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.job_chain'} ); 
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print GVZ "\"$chain_node\"\n";

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="black" BGCOLOR="#EEEEEE">'."\n";

			# Etape courante 
			if ( $node->{job_chain} ) {
				if ($node->{state}) {
					$label .= TableLine(0,  'state', $node->{state} );
				}
				else {
					$label .= TableLine(0,  'state', 'N/A' );
				}
			}
			# Sinon, c'est un job de fin
			else {
				$label .= TableLine(0,  'end_node', $node->{state} );
			}

			# Prochaines etapes
			my($next);
			if ($node->{next_state}) {
				$next = $node->{next_state};
				print GVZ '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=green,style=dashed]'."\n";
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				print GVZ '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=red,style=dashed]'."\n";
			}

			# Si on a un job, on le lie � l'order
			if ($node->{job_chain}) {
				$label .= TableLine(0,  'job_chain', $node->{job_chain} );
				# On stocke les liens pour �viter qu'il se retrouvent dessin�s dans la chaine
				push(@LINKS,'"'.$chain_node.'":'.$unicID.' -> "'.CleanFilename(Basename("$f").'/'.$node->{job_chain}).'/HEAD" [style=dashed]');
			}
			
			$label .= '</TABLE>';
			print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";
			}
	}

	# Les noeuds de fin ?!
	# Liste des noeuds
	if ($doc->{job_chain}->{'job_chain_node.end'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.end'});
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print GVZ "\"$chain_node\"\n";

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";

			# Etape courante 
			$label .= TableLine(0,  'end_node', $node->{state} );
					
			$label .= '</TABLE>';
			print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";
		}
	}
	
	# un file_order_sink ?
	if ($doc->{job_chain}->{file_order_sink}) {
		my(@SINK) = GetRefArray( $doc->{job_chain}->{file_order_sink});
		my($sink);
#		if ($doc->{job_chain}->{file_order_sink}->{state}) {
#			push (@SINK, $doc->{job_chain}->{file_order_sink} );	
#		}
#		else {
#			@SINK = GetRefArray( $doc->{job_chain}->{file_order_sink});
#		}
		foreach my $sink ( @SINK ) {
			my $sink_state = '!file_order_sink';
			my($chain_node) = $sink_state;
			if ($sink->{state}) {
				$sink_state = $sink->{state};
				$chain_node = CleanFilename("$f/".$sink_state);
			}

			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
			$label .= TableLine(0,  'state', $sink->{state} );
			if ( $sink->{move_to}) {
				$label .= TableLine(0,  'move_to',  $sink->{move_to} );
			}
			$label .= TableLine(0,  'remove',  $sink->{remove} );
			$label .= '</TABLE>';
			print GVZ '"'.$chain_node.'" [label=<'.$label.">]\n";		
		}
	}
	print GVZ "}\n";
}

# ---------------------------------------------
# ReadOrder()
# ---------------------------------------------
sub ReadOrder {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /order/);

	$f =~ s/\.order\.xml$//;
	my $chain = CleanChainLabel($f);
	
	# On retrouve la chaine et l'ordre
	my($order);
	($chain,$order) = split(',',CleanChainLabel($f));
	my $orderchain = "$f/$order/$chain";
	my $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'order', $order );
	foreach my $k ('title') {
		if ($doc->{order}->{$k}) {
			$label .= TableLine(0,  $k, $doc->{order}->{$k} );
		}
	}

	# Partie planification
	if ($doc->{order}->{run_time}) {
			$label .= GetSchedule($doc->{order}->{run_time},$orderchain);
	}
	else { # Peut poser un probleme
			$label .= TableLine(0, 'bug', '!Missing run_time!' );		
	}
	
	# Parametres
	if ($doc->{order}->{params}) {
		$label .= GetParams( $doc->{order}->{params});
	}
	
	$label .= '</TABLE>';
	print GVZ '"'.$orderchain.'" [label=<'.$label.">]\n";	
	
	if ($doc->{order}->{state}) {
		my $state = $doc->{order}->{state};
		print GVZ '"'.$orderchain.'" -> "'.CleanFilename(Basename($f).'/').$chain.'/'.$state.'"'."  [style=dashed]\n";	
	}
	else {
		print GVZ '"'.$orderchain.'" -> "'.CleanFilename(Basename($f).'/').$chain.'/HEAD"'."  [style=dashed]\n";	
	}
	if ($doc->{order}->{end_state}) {
		my $end_state = $doc->{order}->{end_state};
		print GVZ '"'.CleanFilename(Basename($f).'/').$chain.'/'.$end_state.'" -> "'.$orderchain.'"'." [color=grey,style=dashed]\n";	
	}
}

# ---------------------------------------------
# ReadProcessClass()
# ---------------------------------------------
sub ReadProcessClass {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /process_class/);

		
	$f =~ s/\.process_class\.xml$//;
	my($process) = CleanChainLabel($f);

	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="cyan" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'process_class', $process );
	foreach my $k ('remote_scheduler','max_processes') {
		$label .= TableLine(0, $k, Encode ( $doc->{process_class}->{$k} ))
			if ( $doc->{process_class}->{$k});
	}
	$label .= '</TABLE>';
	$f =~ s/^\.\//\//;
	print GVZ '"MAC//'.$f.'" [label=<'.$label.">]\n";		
}

# ---------------------------------------------
# ReadLock()
# ---------------------------------------------
sub ReadLock {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /lock/);

	$f =~ s/\.lock\.xml$//;
	my($lock) = CleanChainLabel($f);
	
	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="yellow" BGCOLOR="white">'."\n";
	# exclusive ou non
	if ($doc->{lock}->{max_non_exclusive}) {
		$label .= TableLine(0,  'lock_open', $lock, $doc->{lock}->{max_non_exclusive} );		
	}
	else {
		$label .= TableLine(0,  'lock', $lock, );		
	}
	$label .= '</TABLE>';
	$f =~ s/^\.\//\//;
	print GVZ '"LOCK//'.CleanFilename(Basename( $f ).'/'.$lock).'" [label=<'.$label.">]\n";		

}

# ---------------------------------------------
# ReadConfig()
# ---------------------------------------------
sub ReadConfig {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /config/);

	$f =~ s/\.config\.xml$//;
	my $dir =$f;
	$dir =~ s/\.\///;
	my($config) = CleanChainLabel($f);

	# Chaine ou Chaine,Order ?
	my($chain,$order) = split(',',$config);
	if ($order) {
		# On ajoute un lien de la config � la chaine
		push(@LINKS,'"CFG//'.$config.'" -> "'.$dir.'/HEAD" [style=dotted,color=purple]');
		# Si l'ordre est l�, on ajouter un lien de l'ordre � la config
		push(@LINKS,'"'."$dir/$order/$chain".'" -> "CFG//'.$config.'" [style=dotted,color=purple]');
	}
	else {
		# Si l'ordre est l�, on ajoute un lien de l'ordre � la config
		push(@LINKS,'"'."$dir".'/HEAD" -> "CFG//'.$config.'" [style=dotted,color=purple]');
	}
	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="purple" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'config', $config );
	if ($doc->{settings}->{job_chain}) {
		if ($doc->{settings}->{job_chain}->{order}) {
			if ($doc->{settings}->{job_chain}->{order}->{process}) {
				my @PROCESS = GetRefArray( $doc->{settings}->{job_chain}->{order}->{process} );
				foreach my $p (@PROCESS) {
					my $state = $p->{state};
					$label .= TableLine(0,  'state', $state ); 
					if ($config !~ /,/) {
						push(@LINKS,'"CFG//'.$config.'":'.$unicID.' -> "'.$dir.'/'.$state.'" [style=dotted,color=purple]');
					}
					if ($p->{params}) {
						# Attention, on peut avoir du params => {}
						$label .= GetParams( $p->{params});
					}

				}
			}
		}
	}
	$label .= '</TABLE>';
	print GVZ '"CFG//'.$config.'" [label=<'.$label.">]\n";		
}

# ---------------------------------------------
# ReadSchedule()
# ---------------------------------------------
sub ReadSchedule {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = CallXML($parser,"$hotfolder/$f");
	return unless $doc;
	print "<h3>$f</h3>\n";
	print STDERR Dumper $doc
		if ($debug =~ /schedule/);

	$f =~ s/\.schedule\.xml$//;
	my($schedule) = CleanChainLabel($f);

	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="blue" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'schedule', $schedule );
	# Substitution
	if ($doc->{schedule}->{substitute}) {
		my $substitute = $doc->{schedule}->{substitute};
		$label .= TableLine(1,  'substitute', $substitute );
		push(@LINKS, '"SCHED//'.$schedule.'" -> "SCHED//'.$substitute.'" [color=blue,style=dashed]' );
	}
	foreach my $k ('title', 'valid_to', 'valid_from' ) { 
		$label .= TableLine(1, $k, Encode($doc->{schedule}->{$k}) )
			if ($doc->{schedule}->{$k});
	}
	$label .= GetSchedule( $doc->{schedule} );
	
	$label .= '</TABLE>';
	print GVZ '"SCHED//'.$schedule.'" [label=<'.$label.">]\n";		
}

# ---------------------------------------------
# GetParams()
# ---------------------------------------------
sub GetParams {		
my($param)=@_;
my($label)='';

	return '' unless $param;
        return '' if ($show_params =~ /n/);

	if ($param->{param}) {
                my %New=();
		# Cas des parametres qui ne sont pas dans un tableau
		if ($param->{param}->{name}) {
                        $New{$param->{param}->{name}} = $param->{param}->{value};
		}
		elsif ( ref ( $param->{param} ) eq 'HASH' ) {
			foreach my $k ( keys %{ $param->{param} } ) {
                            $New{$k} = $param->{param}->{$k}->{value};
			}
		}
		else {
			my @PARAMS = GetRefArray( $param->{param} );
			foreach my $p (sort @PARAMS ) {
				my $k = $param->{param}; 
				if ($k->{name}) {
                                        $New{$k->{name}} = $k->{value};
				}
				elsif ($k->{value}) {
                                        $New{$k} = $k->{value};
				}
				else {
					if (ref($k) eq 'HASH') {
                                            $New{'HASH'} = $k->{value};
					}
					else {
                                            $New{$k} = $k->{value};
					}
				}
			}
		}
                foreach my $nk (sort keys %New) {
                    $label .= TableLine(1,  'param', $nk, LongString( NoPassword ( $New{$nk} ) ) );
					Audit('I', 'Privilégier l\'échange de clés au lieu des mots de passe.') 
						if (($nk eq 'auth_method') and  ($New{$nk} eq 'password'));
                }
	}
return $label;
}

# ---------------------------------------------
# GetSchedule()
# ---------------------------------------------
sub GetSchedule {		
my($schedule,$jobname)=@_;
my($label)='';

		# Inclusion
		if ($schedule->{schedule}) {
			$label .= TableLine(0, 'schedule', $schedule->{schedule});
			push(@LINKS, '"SCHED//'.CleanFilename($schedule->{schedule}).'" -> "'.$jobname.'":'.$unicID.' [color=blue,style=dashed]' );

		}

		# Periode directe
		if ($schedule->{period}) {
			$label .= GetScheduleTimes( $schedule->{period}, 0);
		}
		# Jour de la semaine
		if ($schedule->{weekdays}) {
			$label .= GetScheduleWeekdays( $schedule->{weekdays}, 0);
		}				
		# Jour du mois
		if ($schedule->{monthdays}) {
			$label .= GetScheduleMonthdays( $schedule->{monthdays}, 0);
		}
		# Fin de mois
		if ($schedule->{ultimos}) {
			$label .= GetScheduleUltimos( $schedule->{ultimos}, 0);
		}
		# Mois
		if ($schedule->{month}) {
			$label .= GetScheduleMonth($schedule->{month}, 0);
		}
		# Dates specifiques
		if ($schedule->{date}) {
			$label .= GetScheduleDates($schedule->{date}, 0);
		}
		# Vacances
		if ($schedule->{holidays}) {
			$label .= GetScheduleHolidays($schedule->{holidays}, 0);
			# Inclusion
			if ($schedule->{holidays}->{include}) {
				my $inc = '?!';
				if ($schedule->{holidays}->{include}->{live_file}) {
					$inc =  $schedule->{holidays}->{include}->{live_file};
				}
				else {
				}
				$label .= TableLine(0, 'include', $inc );
#				push(@LINKS, '"SCHED//'.CleanFilename($schedule->{schedule}).'" -> "INC//'.$inc.'" [color=blue,style=dashed]' );
			}
		}
				
return $label;
}

sub GetScheduleMonth {
my($month,$tab)=@_;

	my $label;
	my @MONTHS = GetRefArray( $month );
	foreach my $m (@MONTHS) {
		$label .= TableLine(0, 'month', StrMonths($m->{month}));
		if ($m->{period}) {
			$label .= GetScheduleTimes( $m->{period}, $tab+1);
		}
		if ($m->{monthdays}) {
			$label .= GetScheduleMonthdays( $m->{monthdays}, $tab+1);
		}
		if ($m->{days}) {
			$label .= GetScheduleDays( $m->{days}, $tab+1);
		}				
		if ($m->{ultimos}) {
			$label .= GetScheduleUltimos( $m->{ultimos}, $tab+1);
		}				
		if ($m->{weekdays}) {
			$label .= GetScheduleWeekdays( $m->{weekdays}, $tab+1);
		}				
	}
return $label;
}

sub GetScheduleWeekdays {
my($days,$tab)=@_;

	my $label;
	my @WEEKDAYS = GetRefArray( $days );
	foreach my $wd (@WEEKDAYS) {
		if ($wd->{day}) {
			$label .= GetScheduleDays($wd->{day},'weekday', $tab);
		}
	}
return $label;
}

sub GetScheduleMonthdays {
my($days,$tab)=@_;
	
	my $label;
	my @MONTHDAYS = GetRefArray( $days );
	foreach my $md (@MONTHDAYS) {
		if ($md->{day}) {
			$label .= GetScheduleDays($md->{day},'day', $tab);
		}
		if ($md->{weekday}) {
			$label .= GetScheduleDays($md->{weekday},'weekday', $tab);
		}
	}
return $label;
}

sub GetScheduleUltimos {
my($days,$tab)=@_;
	
	my $label;
	my @ULTIMOS = GetRefArray( $days );
	foreach my $md (@ULTIMOS) {
		if ($md->{day}) {
			$label .= GetScheduleDays($md->{day},'ultimo', $tab);
		}
		if ($md->{weekday}) {
			$label .= GetScheduleDays($md->{weekday},'weekday', $tab);
		}
	}
return $label;
}

sub GetScheduleDates {
my($days,$tab)=@_;
	
	my $label = '';
	my @DATES = GetRefArray( $days );
	foreach my $hd (@DATES) {
		$label .= TableLine($tab, 'date', $hd->{date});
		if ($hd->{period}) {
			$label .= GetScheduleTimes( $hd->{period}, $tab+1 );
		}
	}
return $label;
}

sub GetScheduleHolidays {
my($days,$tab)=@_;
	
	my $label = '';
	if ($days->{weekdays}) {
		my @WD;
		foreach my $wd (GetRefArray( $days->{weekdays}->{day} )) {
			push(@WD, $wd->{day} );
		}
		$label .= TableLine($tab, 'holiweekdays', StrWeekdays(join(' ',sort @WD)));
	}
	if ($days->{holiday}) {
		my @HOLIDAYS = GetRefArray( $days->{holiday} );
		foreach my $hd (@HOLIDAYS) {
			$label .= TableLine($tab, 'holiday', $hd->{date});
		}
	}
return $label;
}

sub GetScheduleDays {
my($days,$type,$tab)=@_;
	
	my $label;
	my @DAYS = GetRefArray( $days  );
	foreach my $d (@DAYS) {
		if (($d->{day}) or ($d->{day}==0)) {
			if ($type eq 'weekday') {
				# cas du which
				if ($d->{which}) {
					$label .= TableLine($tab, $type, StrNWeekdays($d->{day},$d->{which}));
				}
				else {
					if ($d->{day}) {
						$label .= TableLine($tab, $type, StrWeekdays($d->{day}));
					}
					else {
						$label .= TableLine($tab, $type, 'N/A' );
					}
				}
			}
			elsif ($type eq 'ultimo') {
					$label .= TableLine($tab, 'day', StrDays('-'.$d->{day}));					
			}
			else {
				$label .= TableLine($tab, $type, StrDays($d->{day}));
			}
		}
		if ($d->{period}) {
			$label .= GetScheduleTimes( $d->{period}, $tab+1 );
		}
	}
return $label;
}

sub GetScheduleTimes {
my($period,$tab)=@_;
	
	my $label;
	my @TIMES = GetRefArray( $period  );
	foreach my $t (@TIMES) {
		foreach my $k ('single_start','begin','end','repeat','absolute_repeat','let_run','when_holiday') {
			if ($t->{$k}) {
				$label .= TableLine($tab, $k, $t->{$k});				
			}
		}
	}
return $label;
}
# ---------------------------------------------
# GetInclude()
# ---------------------------------------------
sub GetInclude {
my($file,$folder)=@_;
return "$folder/$file";
	if (open(FILE,"$hotfolder/$folder/$file")) {
		my $txt='';
		my $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey">'; 
		my $n;
		while (<FILE>) {
			chomp;
			$n++;
			$label .= TableLine(0, $n, $_);
		}
		close(FILE);
		$label .= '</TABLE>'; 
		print GVZ "\"INCLUDE/$folder/$file\" [label=< ".$label." >]\n";
		return $label;
	}
return  "$folder/$file";
}

# ---------------------------------------------
# Interne
# ---------------------------------------------
sub TableLine {
my ($t, $var,$val,$v2)=@_;

	return unless ($val);
	return if ($val eq '');
	
	# Tabulation
	my $maxlevel=5;
	my $tab='';
	foreach (my $i=0;$i<$t;$i++) {
		$tab .= '<td></td>';
	}
	my $bgcolor='';
	if ($val =~ s/^!//) {
		$bgcolor = ' bgcolor="red"';
	}
	$unicID++; # Pour le port
	if ($v2) {
		return '<tr>'.$tab.'<td>'.WriteIcon($var).'</td><td align="left" colspan="'.($maxlevel-$t).'">'.WriteCell($val).'</td><td align="left" PORT="'.$unicID.'">'.WriteCell($v2).'</td></tr>'."\n";
	}
	else {
		return '<tr>'.$tab.'<td'.$bgcolor.'>'.WriteIcon($var).'</td><td align="left" colspan="'.($maxlevel-$t+1).'"  PORT="'.$unicID.'"'.$bgcolor.'>'.WriteCell($val).'</td></tr>'."\n";
		#return '<tr><td>'.WriteIcon($var).'</td><td align="left" colspan="2" PORT="'.$var.'//'.$val.'">'.WriteCell($val).'</td></tr>';
	}
}


sub WriteIcon {
my($txt) = @_;
my $tab ='';

	return 	'<IMG SRC="'.$imgpath.$Img{$txt}.'.png"/>'
		if ($Img{$txt});
return $txt;
}
sub WriteCell {
my($txt) = @_;

	if ($txt =~ s/^IMG\///) {
		return '<IMG SRC="'.$imgpath.$Img{$txt}.'.png"/>'
			if ($Img{$txt});
	}
	else {
		$txt =~ s/&/&amp;/g;
		$txt =~ s/>/&gt;/g;
		$txt =~ s/</&lt;/g;
		
		# On  reprend le HTML de graphviz
		$txt =~ s/\n/<BR\/>/g;
		$txt =~ s/&lt;h1&gt;(.*?)&lt;\/h1&gt;/<B>$1<\/B>/gi;
		$txt =~ s/&lt;p&gt;(.*?)&lt;\/p&gt;/$1/gi;
		# ce qui est permis
		foreach my $p ('SUB','SUP','B','I','U') {
			$txt =~ s/&lt;${p}&gt;(.*?)&lt;\/${p}&gt;/<${p}>$1<\/${p}>/gi;		
		}
		return $txt;
	}
return $txt;
}

sub CleanFilename {
my($t) = @_;
	$t =~ s/^\.//;
	$t =~ s/^\///;
return $t;
}

sub CleanLabel {
my($t) = @_;
	$t =~ s/^\.\///g;
return $t;		
}

sub CleanChainLabel {
my($t) = @_;
	my @I = split('/',$t);
return pop(@I);
}

sub Basename {
my($t) = @_;
	return './' unless $t;
	my @I = split('/',$t);
	pop(@I);
return join('/',@I);
}

sub Encode {
my($t) = @_;
	my $car;
	while ($t =~ /(\\x\{\w\w\})/) {
		$car = $1;
		#$t =~ s/(\\x\{${car}})/&#${car};/g;
		$t =~ s/(\\x\{${car}})/x/g;
	}
return $t;
}

sub GetRefArray {
my($ref)=@_;
	if (ref( $ref ) eq 'ARRAY' ) {
		return @{ $ref } ;
	}
#	elsif (ref( $ref ) eq 'HASH') {
#		# on cree un tableau
#		my @New = ();
#		foreach my $k ( %{ $ref } ) {
#			if ($ref->{$k}) {
#				push(@New, $k );
#				push(@New, $ref->{$k});
#				print STDERR "(($k)) ".$ref->{$k}."\n";
#			}
#		}
#		return  @New ;
#	}
return ( $ref );
}

sub trim {
  my ($string) = @_;
  if (!$string) { return $string; }
  $string =~ s/^\s+//;
  $string =~ s/\s+$//;
  return $string;
}

# ---------------------------------------------
# Lang
# ---------------------------------------------

sub StrMonths {
my($t)=@_;
	$t =~ s/january/1/;
	$t =~ s/february/2/;
	$t =~ s/march/3/;
	$t =~ s/april/4/;
	$t =~ s/may/5/;
	$t =~ s/june/6/;
	$t =~ s/july/7/;
	$t =~ s/august/8/;
	$t =~ s/september/9/;
	$t =~ s/october/10/;
	$t =~ s/november/11/;
	$t =~ s/december/12/;
my @fr = ('','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre');
	my @ND;
	foreach my $d (split(' ',$t)) {
		push(@ND, $fr[$d]);
	}
return join(', ',@ND);
}

sub StrWeekdays {
my($t)=@_;
	$t =~ s/monday/1/;
	$t =~ s/tuesday/2/;
	$t =~ s/wednesday/3/;
	$t =~ s/thursday/4/;
	$t =~ s/friday/5/;
	$t =~ s/saturday/6/;
	$t =~ s/sunday/7/;
my @fr = ('','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche');
	my @ND;
	foreach my $d (split(' ',$t)) {
		push(@ND, $fr[$d]);
	}
return join(', ',@ND);
}

sub StrDays {
my($t)=@_;
	my $ultimos='';
	if ($t=~s/^\-//) {
		$ultimos='-';
	}
	my @ND;
	foreach my $d (split(' ',$t)) {
		if ($d==0) {
			push(@ND, 'Dernier');
		}
		elsif ($d==1) {
			push(@ND, $d.'er');
		}
		else { 
			push(@ND, $ultimos.$d.'�me');
		}
	}
return join(', ',@ND);
}

sub StrNWeekdays {
my($t,$which) = @_;
	$t =~ s/monday/1/;
	$t =~ s/tuesday/2/;
	$t =~ s/wednesday/3/;
	$t =~ s/thursday/4/;
	$t =~ s/friday/5/;
	$t =~ s/saturday/6/;
	$t =~ s/sunday/7/;
my @fr = ('','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche');
	if ($which eq '1') {
		return 'Premier '.$fr[$t];
	}
	elsif ($which eq '2') {
		return 'Deuxi�me '.$fr[$t];
	}
	elsif  ($which eq '3') {
		return 'Troisi�me '.$fr[$t];
	}
	elsif  ($which eq '4') {
		return 'Quatri�me '.$fr[$t];
	}
	elsif  ($which eq '5') {
		return 'Cinqui�me '.$fr[$t]; # n'existe pas mais ce n'est pas normal
	}
	elsif  ($which eq '-1') {
		return 'Dernier '.$fr[$t];
	}
	elsif ($which eq '-2') {
		return 'Avant-dernier '.$fr[$t];
	}
	elsif ($which eq '-3') {
		return 'Ante-penulti�me '.$fr[$t];
	}
	elsif ($which eq '-4') {
		return 'Avant ante-penulti�me '.$fr[$t];
	}
	
return '?!';
}

# ---------------------------------------------
# Clean Path
# ---------------------------------------------

sub CleanPath {
my($str) = @_;
    $str =~ s/\/\.\//\//;
    $str =~ s/\/\//\//;
    $str =~ s/\/\.$//;
return $str;
}

# ---------------------------------------------
# LongString
# ---------------------------------------------

sub LongString {
my($str) = @_;
    $str =~ s/([\.\;\,])(\w)/$1\n$2/g;
return $str;
}

sub NoPassword {
my($str) = @_;
    $str .= " ";
    $str =~ s/password=(.*?) /password=**********/g;
return $str;
}

sub CheckString {
my($str) = @_;
    $str =~ s/[^a-zA-Z0-9\s\-_.,]/\?/g;
return $str;
}

sub CallXML {
my($parser,$folder)=@_;
my $res;
	eval {
	  $res = $parser->XMLin($folder);
	  1;
	} or do {
	  print STDERR "XML non conforme ! : $folder\n";
	  print STDERR $!;
	};
return $res;
}

sub CheckNode {
my($node,$folder)=@_;
    if ($node =~ /^\//) {
        return $node;
    }
    $node = "$folder/$node";
return $node;
}

sub AuditComment {
my($info) = @_;
my($level,$text )= split("\t",$info);
# print STDERR "(($info))";
Audit($level,$text);
}

sub Audit {
my($level,$text) = @_;
#print "<div class='$level'>[$level] $text</div>";
%Bootstrap = ( 
    "I" => "info",
    "W" => 'warning',
    "S" => 'success',
    "E" => 'danger' );
print '<div class="alert alert-'.$Bootstrap{$level}.'" role="alert">
  <a href="#" class="alert-link">'.$text.'</a>
</div>';

}

