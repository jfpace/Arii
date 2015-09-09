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

use strict;
use Getopt::Long;
use XML::Simple;
use Data::Dumper;
use Encode;
use File::Basename;

my ($config, $events, $show_params, $show_events, $show_def, $show_doc, $show_time, $show_chains, $show_jobs, $show_config, $status, $scheduler_xml, $images, $path, $file, $event_file, $debug, $lang, $code, $target, $splines, $rankdir, $help) = 
   (Basename($ENV{SCHEDULER_INI}), 'y', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', '', '', 'images', '.*',  '.*',  '.*', '', 'fr', 'n', '', 'polyline', 'TB'  );

# check command line arguments
usage() if ( ! GetOptions(	'help|h'     => \$help,
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

my($eventsfolder) = $config.'/events';

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

my($unicID); # Pour les liens globaux

# Parametres
if ($code =~ /^n/) {
	$code = 'n';
}

print "digraph OSJS {\n";
print "fontname=arial\n";
print "fontsize=8\n";
print "splines=$splines\n";
print "randkir=$rankdir\n";
print "node [shape=plaintext,fontname=arial,fontsize=8]\n";
print "edge [shape=plaintext,fontname=arial,fontsize=8,decorate=true,compound=true]\n";
print "bgcolor=transparent\n";

# On traite les status
if ($status ne '') {
	ReadStatus($status);
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

my($hotfolder);
my($mainfolder) = '';

# Configuration 
#ReadScheduler('scheduler.xml');

# Les evenements
if ($events !~ /^n/i) {
	if (opendir(DIR, $eventsfolder)) {;
            my($f);
            foreach $f (readdir(DIR)) {
                    if (($f =~ /\.actions\.xml$/) and ($f =~ /${event_file}/)) {
                            ReadEvent($f,$eventsfolder);
                    }	
            }
            closedir(DIR);
	}
}

# Lecture des HotFolders
foreach $mainfolder ('live','cache', 'draft') {
	$hotfolder = $config.'/'.$mainfolder;
	Folder('.');
}

# Repertoires REMOTE
$mainfolder = 'remote';
my ($maindir);
if (opendir(DIR, $config.'/remote')) {
		my @FILES = sort readdir(DIR);
		closedir(DIR);
		my $nominal = $config;
		foreach my $f (@FILES) {
			if (($f eq '_all') or ($f =~ /#/)) {
				$maindir = $f;
				$config = $nominal.'/remote/';
				$hotfolder = $config.'/'.$f;
				Folder('.');
			}
		}
}

sub Folder {
my($folder)=@_;

	my $foldernode;
        my $foldername = CleanLabel($folder);
	if ($folder ne '.') {
		print "subgraph cluster$dir {\n";
		if ($mainfolder eq 'remote') {
			print 'style="dashed"'."\n";
			print 'label="['.$maindir.'] '.$foldername."\"\n";
		}
		else {
			print 'label="'.$foldername."\"\n";
		}
	}

	if (opendir(DIR, $hotfolder.'/'.$folder)) {

		my @FILES = sort readdir(DIR);
		closedir(DIR);
		foreach my $f (@FILES) {

			my($thisfile) = $folder.'/'.$f;
			if (-d $hotfolder.'/'.$folder.'/'.$f) {
				if (($f ne '.') and ($f ne '..')) {
					$dir++;
					Folder($thisfile);
				}
			}
			elsif (CleanPath($hotfolder.'/'.$folder) =~ /$path/) {
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
				}
			}
		}
	}

	if ($folder ne '.') {
		print "}\n";
	}
}

# Les bouts de config a integrer dans scheduler.xml
if ($scheduler_xml ne '') {
	ReadSchedulerXML($scheduler_xml);
}

# On ajoute les liens
my $l;
foreach $l (@LINKS) {
	print "$l\n";
}

# Evenements
foreach my $action (keys %ShowEvent) {
    if ($show_events =~ /n/) {
        print '"EVENT//'.$action.'" [shape=ellipse,label="'.$action.'",style=filled,fillcolor="'.$BGColor{events}.'"'."]\n";
    }
    else {
        print '"EVENT//'.$action.'" [label=<'.$Events{$action}->{label}.">]\n";
    }
}

# Fin du graph
print "}\n";

# ============================================
# GRAPHS CONTEXTUELS
# ============================================

exit 0
	if ($target eq '');
	
# Dessiner tous les ordres
#foreach my $order (@GRAPH_ORDERS) {
#}

# --------------------------------------------
# usage()
# --------------------------------------------
sub usage {
   print STDERR "
oss2gvz.pl --config=<config>

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
	my $doc = $parser->XMLin("$hotfolder/$f");
	if ($debug =~ /job/) {
		print STDERR "$hotfolder/$f\n";
		print STDERR Dumper $doc
	}

	$f =~ s/\.job\.xml$//;
	my $jobname = CleanFilename($f);
	
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
                                print '"EVENT//'.$action.'"'."\n";
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
	print '"'.$jobname.'" [label=<'.$label.">]\n";		
	push(@JOBS,$jobname);
        
        # Un evenement le pousse ?
        if ($StartJob{$jobname}) {
            my $action = $StartJob{$jobname};
            # pour le positionner
            print '"EVENT//'.$action.'"'."\n";
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
	my $doc = $parser->XMLin("$hotfolder/$f");
	print STDERR Dumper $doc
		if ($debug =~ /chain/);

	$f =~ s/\.job_chain\.xml$//;
	my $local = $f; # localisation
	$local =~ s/^\.\///;
	my($chain) = CleanChainLabel($f);
	print "subgraph \"cluster$f$chain\" {\n";
	print "style=filled;\n";
	print "color=lightgrey;\n";
	print 'label="'.$local."\"\n";

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
	# Pour les ordres
	 print '"'.$chain_node.'" [label=<'.$label.">]\n";		
	# A voir a quoi ca sert ?
	#print '"'.$local.'" [label=<'.$label.">]\n";
	
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
		print '"'.$chain_node.'" [label=<'.$label.">]\n";		
	}

	# Liste des noeuds
	if ($doc->{job_chain}->{job_chain_node}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{job_chain_node} ); 
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print "\"$chain_node\"\n";

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
					print '"'.$chain_node.'" -> "'.$chain_node.'" [color=red,dir=back]'."\n";					
				}
				elsif ($node->{on_error} eq 'setback') {
					print '"'.$chain_node.'" -> "'.$chain_node.'" [color=red,style=dashed,dir=back]'."\n";					
				}
			#	$label .= TableLine(0,  'on_error', $node->{on_error} );
			}

			# Prochaines etapes
			my($next);
			if ($node->{next_state}) {
				$next = $node->{next_state};
				print '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=green,style=dashed]'."\n";
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				print '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=red,style=dashed]'."\n";
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
			print '"'.$chain_node.'" [label=<'.$label.">]\n";
			}
	}
	elsif ($doc->{job_chain}->{'job_chain_node.job_chain'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.job_chain'} ); 
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print "\"$chain_node\"\n";

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
				print '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=green,style=dashed]'."\n";
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				print '"'.$chain_node.'" -> "'.CleanFilename("$f/$next").'" [color=red,style=dashed]'."\n";
			}

			# Si on a un job, on le lie � l'order
			if ($node->{job_chain}) {
				$label .= TableLine(0,  'job_chain', $node->{job_chain} );
				# On stocke les liens pour �viter qu'il se retrouvent dessin�s dans la chaine
				push(@LINKS,'"'.$chain_node.'":'.$unicID.' -> "'.CleanFilename(Basename("$f").'/'.$node->{job_chain}).'/HEAD" [style=dashed]');
			}
			
			$label .= '</TABLE>';
			print '"'.$chain_node.'" [label=<'.$label.">]\n";
			}
	}

	# Les noeuds de fin ?!
	# Liste des noeuds
	if ($doc->{job_chain}->{'job_chain_node.end'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.end'});
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			print "\"$chain_node\"\n";

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";

			# Etape courante 
			$label .= TableLine(0,  'end_node', $node->{state} );
					
			$label .= '</TABLE>';
			print '"'.$chain_node.'" [label=<'.$label.">]\n";
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
			print '"'.$chain_node.'" [label=<'.$label.">]\n";		
		}
	}
	print "}\n";
}

# ---------------------------------------------
# ReadOrder()
# ---------------------------------------------
sub ReadOrder {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$hotfolder/$f");
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
	print '"'.$orderchain.'" [label=<'.$label.">]\n";	
	
	if ($doc->{order}->{state}) {
		my $state = $doc->{order}->{state};
		print '"'.$orderchain.'" -> "'.CleanFilename(Basename($f).'/').$chain.'/'.$state.'"'."  [style=dashed]\n";	
	}
	else {
		print '"'.$orderchain.'" -> "'.CleanFilename(Basename($f).'/').$chain.'/HEAD"'."  [style=dashed]\n";	
	}
	if ($doc->{order}->{end_state}) {
		my $end_state = $doc->{order}->{end_state};
		print '"'.CleanFilename(Basename($f).'/').$chain.'/'.$end_state.'" -> "'.$orderchain.'"'." [color=grey,style=dashed]\n";	
	}
}

# ---------------------------------------------
# ReadProcessClass()
# ---------------------------------------------
sub ReadProcessClass {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$hotfolder/$f");
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
	print '"MAC//'.$f.'" [label=<'.$label.">]\n";		
}

# ---------------------------------------------
# ReadLock()
# ---------------------------------------------
sub ReadLock {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$hotfolder/$f");
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
	print '"LOCK//'.CleanFilename(Basename( $f ).'/'.$lock).'" [label=<'.$label.">]\n";		

}

# ---------------------------------------------
# ReadConfig()
# ---------------------------------------------
sub ReadConfig {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$hotfolder/$f");
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
	print '"CFG//'.$config.'" [label=<'.$label.">]\n";		
}

# ---------------------------------------------
# ReadSchedule()
# ---------------------------------------------
sub ReadSchedule {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$hotfolder/$f");
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
	print '"SCHED//'.$schedule.'" [label=<'.$label.">]\n";		
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
		print "\"INCLUDE/$folder/$file\" [label=< ".$label." >]\n";
		return $label;
	}
return  "$folder/$file";
}

# ---------------------------------------------
# ReadEvent()
# ---------------------------------------------
sub ReadEvent {
my($f,$eventfolder) = @_;
    my $parser = XML::Simple->new( KeepRoot => 1 );
    my $doc = $parser->XMLin("$eventfolder/$f");
    print STDERR Dumper $doc
            if ($debug =~ /event/);

    $f =~ s/\.actions\.xml$//;
    my $actionfile = CleanFilename($f);
    foreach my $actions (GetRefArray($doc->{actions})) {
        # Cas simple 
        if ($actions->{action}->{name}) {
            EventAction($actions->{action}->{name}, $actions->{action});
        }
        # Sinon c'est un HASH 
        else {
            foreach my $action ( keys %{ $actions->{action} } ) {
                EventAction($action ,$actions->{action}->{$action});
            }
        }
    }
}

sub EventAction {
my($name,$action) = @_;
my($label)='';

    $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="'.$BGColor{events}.'">'."\n";
    $label .= TableLine(0, 'event', $name );

    # Evenements entrants
    foreach my $events (GetRefArray( $action->{events})) {
        my $event_group = $events->{event_group};
        $label .= TableLine(1, 'group', $event_group->{group} )
            if ($event_group->{group});
        $label .= TableLine(1, 'logic', $event_group->{logic} )
            if ($event_group->{logic});
        foreach my $event ( GetRefArray( $event_group->{event} )) {
            foreach my $f ('event_name','comment','event_class','event_id','exit_code') {
                $label .= TableLine(2, $f, $event->{$f} )
                    if ($event->{$f});
            }
            my $rule='!CLASS';
            if ($event->{event_class}) {
                $rule = $event->{event_class};
            }
            if ($event->{event_id}) {
                $rule .= '|'.$event->{event_id};
            }                            
            $EventRule{$rule} = $name;
        }
    }
    # Commandes entrants
    foreach my $commands ($action->{commands}) {
        my $command = $commands->{command};
        $label .= TableLine(1, 'name', $command->{name} )
            if ($command->{name});
        foreach my $start ( GetRefArray( $command->{start_job} )) {
            foreach my $j ('job') {
                if ($start->{$j}) {    
                    my $job = $start->{$j};
                    $label .= TableLine(2, $j, $job );

                    # On sauvegarde tous les démarrages de job
                    $StartJob{$job} = $name;
                    # On renvoie sur le job
                    # push(@LINKS, '"EVENT//'.$receive.'" -> "'.$job.'" [color=black,style=dotted]');

                    # On conserve l'evenement pour le mettre dans le meme folder que le job
                    #my $dir = dirname($job);
                    #$JobGroup{ $dir } .= 'EVENT//'.$receive.'|';

                    # debug  
                    #$label .= TableLine(0, "dir" , $dir  );
                    #$label .= TableLine(0, "events" , $JobGroup{ $dir }  );
                }
            }
        }
    }

    $label .= '</TABLE>';
    $Events{$name}->{label} = $label;
}

# Pour disposer du schema complet
sub ReadEventFormula {
my($f,$eventfolder) = @_;
my($label)='';

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$eventfolder/$f");
	print STDERR Dumper $doc
		if ($debug =~ /event/);
	
	$f =~ s/\.actions\.xml$//;
	my $actionsname = CleanFilename($f);

	# Quand 1 seule action : actions->action->events
	# Sinon : actions->action->{ACTION}->events
	
	print "subgraph \"clusterEVENT$actionsname\" {";
	print "label=\"$actionsname\"\n";
	print "bgcolor=white\n";
	
	# La gestion des événements est un sous-graph pour chaque Action
	foreach my $act (GetRefArray($doc->{actions}->{action})) {
		my $action = $doc->{actions}->{action}->{$act};
		# action est une chaine ou un hash ?
		my $actionname = 'ACTION';
#		if (ref($act) eq 'HASH') {
#			my $action = $act;
#			if ($action->{name}) {
#				$actionname = $action->{name};
#			}
#		}
#		else {
#			$actionname = $act;
#			my $action = $doc->{actions}->{action}->{$act};
#		}
		my $labelAct = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="lightgrey">'."\n";
		$labelAct .= TableLine(0,  'action', $actionname );	

		# $labelAct .= TableLine(0,  'logic', $action->{events}->{logic} );	

		# Logique de regroupement
		my $formula = 'and';
		if ($action->{events}->{logic}) {
			$formula = $action->{events}->{logic};
		}
		$formula =~ s/and/&amp;&amp;/;
		$formula =~ s/or/\|\|/;

		# print '"EVT//'.$actionsname.'/logic" [label="'.$logic.'", shape=ellipse]';	

		# On traite les groupes d'evenements
		
		
		# On regroupe
		my $evtnum =0;
		my $idlogic = 0;
		my $grp = 'group';
		my @GROUPS = GetRefArray( $action->{events}->{event_group} );
		foreach my $event_group ( keys %{ @GROUPS } ) {
			my $event_grp = $action->{events}->{event_group}->{$event_group};
			if( $event_grp->{group} ) {
				$grp = $event_grp->{group};
			}
			my $labelGrp = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="lightgrey" BGCOLOR="lightgrey">'."\n";		
			$labelGrp .= TableLine(0,  'event_group', $grp );
			if ($event_grp->{event_class}) {
				$labelGrp .= TableLine(0,  'event_class', $event_grp->{event_class} );
			}
			my $logic = 'and';
			$idlogic++;
			if (my $event_group->{logic}) {
				$logic = $event_group->{logic};
			}
			$logic =~ s/and/&amp;&amp;/;
			$logic =~ s/or/\|\|/;
			# $labelGrp .= TableLine(0,  'logic', $logic	);
			$labelGrp .= '</TABLE>';
			print '"'.$actionsname.'/'.$actionname.'/'.$grp.'" [label=<'.$labelGrp.">]\n";			
			
			# Un nouveau noeud pour chaque element
			my @EVENTS =  GetRefArray( $event_grp->{event} ) ;
			foreach my $event(@EVENTS) {
				my $labelEvt = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="blue" BGCOLOR="lightgrey">'."\n";
				$evtnum++;
				foreach my $e ( 'event_name', 'event_title', 'event_class', 'event_id', 'job_name', 'exit_code' ) {
					$labelEvt .= TableLine(0,  $e, $event->{$e} )
						if ($event->{$e});
					# Si on a un matching sur le job_name...
					if ($e eq 'job_name') {
						# On joint le job et l'�v�nement
						if ($event->{job_name}) {
							my $j = $event->{job_name};
							foreach (@JOBS) {
								if (/$j/) {
									push(@LINKS,'"'.$_.'" -> "'.$action.'/'.$evtnum.'" [color=blue,style=dotted]');
								}
							}
						}
					}
				}
				$labelEvt .= '</TABLE>';
				print '"'.$action.'/'.$evtnum.'" [label=<'.$labelEvt.">]\n";	
				if ($#EVENTS>0) {
					print "\"LOGIC//".$action.'/'.$idlogic.'/'.$logic."\" [label=\"$logic\",shape=ellipse,color=lightgrey]\n";
					# On raccorde l'evenement a son groupe logique
					push(@LINKS, '"'.$action.'/'.$evtnum.'" -> "LOGIC//'.$action.'/'.$idlogic.'/'.$logic.'" [color=grey,style=dotted]');
				}
			}
			if ($#EVENTS>0) {
				push(@LINKS, '"LOGIC//'.$action.'/'.$idlogic.'/'.$logic.'" -> "'.$actionsname.'/'.$actionname.'/'.$grp.'" [color=grey,style=dotted]');
			}
			else {
					push(@LINKS, '"'.$action.'/'.$evtnum.'"-> "'.$actionsname.'/'.$actionname.'/'.$grp.'" [color=grey,style=dotted]');
			}
			# groupe est raccord� � la fomule qui est raccord� � l'�v�nement
			if ($#GROUPS>0) {
				print "\"RULE//".$actionsname.'/'.$actionname.'/'.$formula."\" [label=\"$formula\",shape=ellipse,color=grey]\n";
				push(@LINKS,  '"'.$actionsname.'/'.$actionname.'/'.$grp.'" -> "RULE//'.$actionsname.'/'.$actionname.'/'.$formula.'" [color=grey,style=dotted]');
			}
		}
		if ($#GROUPS>0) {		
			push(@LINKS,  '"RULE//'.$actionsname.'/'.$actionname.'/'.$formula.'" -> "'.$actionsname.'/'.$actionname.'" [color=grey,style=dotted]'); 			
		}
		else {
			push(@LINKS,  '"'.$actionsname.'/'.$actionname.'/'.$grp.'" -> "'.$actionsname.'/'.$actionname.'" [color=grey,style=dotted]'); 
		}
		$labelAct .= '</TABLE>';
		print '"'.$actionsname.'/'.$actionname.'" [label=<'.$labelAct.">]\n";			

		# On genere le graph des commandes a executer
		foreach my $command ( GetRefArray( $action->{commands} ) ) {
			if ($command->{command}->{start_job}) {
				my $labelCmd = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="grey">'."\n";		
				$labelCmd .= TableLine(0, 'start_job', $command->{command}->{start_job}->{name} )
					if ($command->{command}->{start_job}->{name});
				
				# Si on demarre un job, on fait le lien sur ce job
				if ($command->{command}->{start_job}->{job}) {
					$labelCmd .= TableLine(0, 'standalone', $command->{command}->{start_job}->{job} );
					push(@LINKS, '"'.$actionsname.'/'.$command.'" -> "'.$command->{command}->{start_job}->{job}.'" [color=blue]' );
				}
				
				$labelCmd .= TableLine(0, 'scheduler_name', $command->{command}->{start_job}->{scheduler_name} )
					if ($command->{command}->{start_job}->{scheduler_name});
				$labelCmd .= TableLine(0, 'scheduler_port', $command->{command}->{start_job}->{scheduler_port} )
					if ($command->{command}->{start_job}->{scheduler_port});

				$labelCmd .= '</TABLE>';
				print '"'.$actionsname.'/'.$command.'" [label=<'.$labelCmd.">]\n";
				
				# On rattache au noeud
				print  '"'.$actionsname.'/'.$actionname.'" -> "'.$actionsname.'/'.$command."\" \n";
			}
			if ($command->{remove_event}) {
				foreach my $event ( GetRefArray( $command->{remove_event} ) ) {
					my $labelCmd = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="grey">'."\n";		
					$labelCmd .= TableLine(0, 'remove_event', $event->{event}->{event_name} );
					$labelCmd .= TableLine(0, 'event_class', $event->{event}->{event_class} );
					$labelCmd .= TableLine(0, 'job_name', $event->{event}->{event}->{job_name} )
						if ($event->{event}->{event}->{job_name});
					$labelCmd .= TableLine(0, 'exit_code', $event->{event}->{event}->{exit_code} )
						if ($event->{event}->{event}->{exit_code});
					$labelCmd .= '</TABLE>';
					print '"'.$actionsname.'/'.$event.'" [label=<'.$labelCmd.">]\n";
				
					# On rattache au noeud
					print  '"'.$actionsname.'/'.$actionname.'" -> "'.$actionsname.'/'.$event."\" [color=grey]\n";
				}
			}
		}

	}
	
	
	print "}\n";

}

# ---------------------------------------------
# ReadSchedulerXML()
# ---------------------------------------------
sub ReadScheduler {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$config/$f");
	print STDERR Dumper $doc
		if ($debug =~ /config/);
	
	if ($doc->{spooler}->{config}) {
		my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="black" BGCOLOR="grey">'."\n";
		if ( $doc->{spooler}->{config}->{spooler_id} ) {
			$label .= TableLine(0, 'spooler_id', $doc->{spooler}->{config}->{spooler_id});
		}
		foreach my $k ('port','supervisor') {
			if ($doc->{spooler}->{config}->{$k}) {
				$label .= TableLine(1, $k, $doc->{spooler}->{config}->{$k});
			}
		}
		# Cas du tcp et de l'udp
		foreach my $k ( 'tcp_port','udp_port') {
			if ($doc->{spooler}->{config}->{$k}) {
				my $port = $doc->{spooler}->{config}->{$k};
				my $protocol = $$k;
				$protocol =~ s/_port$//;
				$label .= TableLine(1, 'port', $doc->{spooler}->{config}->{$k}.' ('.$protocol.')');
			}
		}

		$label .= "</TABLE>";
		print '"SCHEDCFG" [label=<'.$label.">]\n";
	}
}
# ---------------------------------------------
# ReadSchedulerXML()
# ---------------------------------------------
sub ReadSchedulerXML {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$config/$f");
	print STDERR Dumper $doc
		if ($debug =~ /config/);
	
	# La gestion des �v�nements est un sous-graph pour chaque Action
	if ($doc->{spooler}) {
		# Process class ?
		if ($doc->{spooler}->{process_classes}) {
			if ( ref ( $doc->{spooler}->{process_classes} ) eq 'HASH' ) {
				foreach my $process ( keys %{ $doc->{spooler}->{process_classes}->{process_class} } ) {
					my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="cyan" BGCOLOR="white">'."\n";
					$label .= TableLine(0, 'process_class', $process );
					$label .= TableLine(1, 'max_processes', $doc->{spooler}->{process_classes}->{process_class}->{$process}->{max_processes} );
					$label .= "</TABLE>";
					print '"MAC///'.$process.'" [label=<'.$label.">]\n";		
				}
			}
		}
		# Locks ?
		if ($doc->{spooler}->{locks}) {
			if ( ref ( $doc->{spooler}->{locks} ) eq 'HASH' ) {
				foreach my $lock ( keys %{ $doc->{spooler}->{locks}->{lock} } ) {
					my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="yellow" BGCOLOR="white">'."\n";
					$label .= TableLine(0, 'lock', $lock );
					$label .= "</TABLE>";
					print '"LOCK///'.$lock.'" [label=<'.$label.">]\n";		
				}
			}
		}
	}
}

# ---------------------------------------------
# ReadStatus()
# ---------------------------------------------
sub ReadStatus {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin($f);
	print STDERR Dumper $doc
		if ($debug =~ /status/);

	# Noeud de status
	my $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="grey">';	
	if ($doc->{spooler}->{answer}->{time}) {
		$label .= TableLine(0, 'OK', $doc->{spooler}->{answer}->{time} );		
	}
	if ($doc->{spooler}->{answer}->{state}) {
		my $state = $doc->{spooler}->{answer}->{state};
		foreach my $k ('id', 'host', 'tcp_port', 'state', 'pid', 'cpu_time', 'spooler_running_since', 'version' ) {
			if ($state->{$k}) {
				$label .= TableLine(0, $k, $state->{$k} );		
			}
		}
		if ($state->{jobs}) {
			if ($state->{jobs}->{job}) {
			my @JOBS =  GetRefArray($state->{jobs}->{job});
				GetJobs($state->{jobs}->{job});
			}
		}
	}
	$label .= '</TABLE>';
	print '"JOBSCHEDULER" [label=<'.$label.">]\n";
}

sub GetJobs {
my($jobs)=@_;

	foreach my $j (keys %{ $jobs }) {
		if ($jobs->{$j}->{state}) {
			print "\"$j\" [color=red]\n";
#			.$jobs->{$j}->{state};
		}
	}
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

