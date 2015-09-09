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
use Encode qw( encode decode );

my ($config, $hotfolder, $order, $job_chain, $job, $config_file, $events, $show_params, $show_events, $show_def, $show_doc, $show_time, $status, $scheduler_xml, $images, $path, $file, $event_file, $debug, $lang, $code, $target, $splines, $rankdir, $schedule,  $help) = 
   (Basename($ENV{SCHEDULER_INI}), 'live', '^$', '^$', '^$', '^$', 'y', 'y', 'n', 'n', 'n', 'n', '', '', 'images', '.*',  '.',  '.*', '', 'fr', 'n', '', 'polyline', 'TB', ''  );

# check command line arguments
usage() if ( ! GetOptions(	'help|h'     => \$help,
                                'config=s'     => \$config,
                                'hotfolder=s'    => \$hotfolder,
                                'events=s',     => \$events,
                                'order=s',     => \$order,
                                'job_chain=s',     => \$job_chain,
                                'job=s',     => \$job,
								'schedule=s',	=> \$schedule,
                                'show_params=s',     => \$show_params,
                                'show_events=s',     => \$show_events,
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

my(@JOBS);   # Pour les start_job
my(@CHAINS);   # Pour les add_order
my(@LINKS);

my(%Done);   # Tout ce qui a d j   t  fait

# Tous les evenements
my(%Events); 
my(%StartJob);
my(%EventRule);
my(%ShowEvent);

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

my $objdir=$config.'/'.$hotfolder;
# On regarde dans le path courant
if (opendir(DIR, $objdir.'/'.$path)) {
	my @FILES = sort readdir(DIR);
	closedir(DIR);
	foreach my $f (@FILES) {
		if ($f =~ /$order\.order\.xml$/) {
			print STDERR "Order	$f\n"; 
			ReadOrder($path.'/'.$f);
			if ($f =~ /^(.*),/) {
				$job_chain = $1;
				ReadChain($path.'/'.$job_chain.'.job_chain.xml');				}
		}
	}
	# On traite les job_chains
	foreach my $f (@FILES) {
		if ($f =~ /$job_chain\.job_chain\.xml$/) {
			print STDERR "Chain	$f\n"; 
			ReadChain($path.'/'.$f);
		}
	}	
	# On traite les config
	foreach my $f (@FILES) {
		if ($f =~ /$config_file\.config\.xml$/) {
			print STDERR "Config	$f\n"; 
			ReadConfig($path.'/'.$f);
		}
	}
	# On traite les jobs
	foreach my $f (@FILES) {
		if ($f =~ /$job\.job\.xml$/) {
			print STDERR "Job	$f\n"; 
			ReadJob($path.'/'.$f);
		}
	}	
	# On traite les schedules
	foreach my $f (@FILES) {
		if ($f =~ /$schedule\.schedule\.xml$/) {
			print STDERR "Sched. $f\n"; 
			ReadSchedule($path.'/'.$f);
		}
	}

        # Boucle ou recursif ?	
        foreach my $c (@CHAINS) {
            print STDERR "Chain	$c\n"; 
            ReadChain( $c.'.job_chain.xml');
        }

        # Liens ?	
        foreach my $l (@LINKS) {
            print $l;
        }
}
print "}\n";

# ---------------------------------------------
# ReadOrder()
# ---------------------------------------------
sub ReadOrder {
my($f) = @_;
	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin($objdir.'/'.$f);

	print STDERR Dumper $doc
		if ($debug =~ /order/);

	$f =~ s/\.order\.xml$//;
	my $chain = CleanChainLabel($f);
	
	# On retrouve la chaine et l'ordre
	my($order);
	($chain,$order) = split(',',CleanChainLabel($f));
	my $orderchain = "$f";
	my $label = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'order', $order );

	foreach my $k ('title') {
		if ($doc->{order}->{$k}) {
			$label .= TableLine(0,  $k, CheckString ( $doc->{order}->{$k} ) );
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
	PrintNode( $orderchain, '[label=<'.$label.'>]' );	
	
	if ($doc->{order}->{state}) {
		my $state = $doc->{order}->{state};
		PrintLink( $orderchain, CleanFilename(Basename($f).'/').$chain.'/'.$state, "[style=dashed]");
		
	}
	else {
		PrintLink( $orderchain, CleanFilename(Basename($f).'/').$chain.'/HEAD',"[style=dashed]");	
	}
	if ($doc->{order}->{end_state}) {
		my $end_state = $doc->{order}->{end_state};
		PrintLink( CleanFilename(Basename($f).'/').$chain.'/'.$end_state, $orderchain, "[color=grey,style=dashed]" );	
	}
}

# ---------------------------------------------
# ReadChain()
# ---------------------------------------------
sub ReadChain {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
        if (! -f "$objdir/$f") {
            print "\"$objdir/$f\" [BGCOLOR=red]\n";
            return;
        }

	my $doc = $parser->XMLin("$objdir/$f");
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
			$label .= TableLine(0, $k, CheckString( $doc->{job_chain}->{$k} ) );
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
		$label .= TableLine(1,  'directory', $doc->{job_chain}->{file_order_source}->{directory} );
		$label .= TableLine(1,  'regex', $doc->{job_chain}->{file_order_source}->{regex} );
		$label .= '</TABLE>';
		PrintNode( $chain_node, '[label=<'.$label.'>]' );		
	}

	# Liste des noeuds
	if ($doc->{job_chain}->{job_chain_node}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{job_chain_node} ); 
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			PrintNode( $chain_node );

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
					PrintLink ( $chain_node, $chain_node, '[color=red,dir=back]' );					
				}
				elsif ($node->{on_error} eq 'setback') {
					PrintLink ( $chain_node, $chain_node, '[color=red,style=dashed,dir=back]' );					
				}
			#	$label .= TableLine(0,  'on_error', $node->{on_error} );
			}

			# Prochaines etapes
			my($next);
			if ($node->{next_state}) {
				$next = $node->{next_state};
				PrintLink ( $chain_node, CleanFilename("$f/$next"), '[color=green,style=dashed]' );
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				PrintLink ( $chain_node, CleanFilename("$f/$next"), '[color=red,style=dashed]' );
			}
						
			# Si on a un job, on le lie ? l'order
			if ($node->{job}) {
				$label .= TableLine(0,  'job_chain', $node->{job} );
				# On stocke les liens pour ?viter qu'il se retrouvent dessin?s dans la chaine
				my $joblink;
				if ($node->{job} =~ s/^\///) {	
					$joblink = $node->{job};
				}
				else {
					$joblink = CleanFilename2(Basename("$f"),$node->{job});
				}
				# On ajoute le job
				PrintLink( $chain_node, $joblink, '[style=dotted]' );
				ReadJob($joblink.'.job.xml');
			}
			
			$label .= '</TABLE>';
			PrintNode ($chain_node, '[label=<'.$label.'>]');
			}
	}
	elsif ($doc->{job_chain}->{'job_chain_node.job_chain'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.job_chain'} ); 
		my($node);
		foreach $node ( @NODES ) {
			#my($chain_node) = CleanFilename("$f/".$node->{state});
                        my($chain_node) = CleanFilename2(Basename("$f"),$node->{state});
			PrintNode ( $chain_node );

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";

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
				PrintLink( $chain_node, CleanFilename2(Basename("$f"),$next), "[color=green,style=dashed]" );
			}
			if ($node->{error_state}) {
				$next = $node->{error_state};
				PrintLink( $chain_node, CleanFilename2(Basename("$f"),$next), "[color=red,style=dashed]" );
			}
						
			# Si on a un job, on le lie ? l'order
			if ($node->{job_chain}) {
				$label .= TableLine(0,  'job_chain', $node->{job_chain} );
				# On stocke les liens pour ?viter qu'il se retrouvent dessin?s dans la chaine
				PrintLink( $chain_node, CleanFilename($node->{job_chain}.'/HEAD'), "[style=dashed]", $unicID );
                                # ReadChain($node->{job_chain}.'.job_chain.xml');
                                push(@CHAINS, $node->{job_chain});

                                # On conserve le tableau des nouvelles chaines
                                # ?! push(@CHAINS, CleanFilename(Basename("$f").'/'.$node->{job_chain}) );
			}
			
			$label .= '</TABLE>';
			PrintNode ( $chain_node, '[label=<'.$label.'>]' );
		}
	}

	# Les noeuds de fin ?!
	# Liste des noeuds
	if ($doc->{job_chain}->{'job_chain_node.end'}) {
		my(@NODES) = GetRefArray( $doc->{job_chain}->{'job_chain_node.end'});
		my($node);
		foreach $node ( @NODES ) {
			my($chain_node) = CleanFilename("$f/".$node->{state});
			PrintNode ( $chain_node );

			# On prepare le label du noeud
			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";

			# Etape courante 
			$label .= TableLine(0,  'end_node', $node->{state} );
					
			$label .= '</TABLE>';
			PrintNode ( $chain_node, '[label=<'.$label.'>]' );
		}
	}
	
	# un file_order_sink ?
	if ($doc->{job_chain}->{file_order_sink}) {
		my(@SINK) = GetRefArray($doc->{job_chain}->{file_order_sink} );
		foreach my $sink ( @SINK ) {
			my $sink_state = '!file_order_sink';
			my($chain_node) = $sink_state;
			if ($sink->{state}) {
				$sink_state = $sink->{state};
				$chain_node = CleanFilename("$f/".$sink_state);
			}

			my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="grey" BGCOLOR="white">'."\n";
			$label .= TableLine(0,  'sink_node', $sink->{state} );
			if ( $sink->{move_to}) {
				$label .= TableLine(0,  'move_to',  $sink->{move_to} );
			}
			$label .= TableLine(0,  'remove',  $sink->{remove} );
			$label .= '</TABLE>';
			PrintNode( $chain_node, '[label=<'.$label.'>]' );		
		}
	}

        # Avant de ferme la chaine, on complete par la config
        ReadConfig($f.'.config.xml');
	print "}\n";

}
# ---------------------------------------------
# ReadJob()
# ---------------------------------------------
sub ReadJob {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	# on teste la presence du fichier 
	$file = $f;
	$f =~ s/\.job\.xml$//;

	my $jobname = CleanFilename($f);
	if (! -f "$objdir/$file") {
            print "\"".$file."\" [shape=ellipse,color=red]\n";
            return;
	}
	my $doc = $parser->XMLin("$objdir/$file");
	if ($debug =~ /job/) {
		print STDERR "$objdir/$file\n";
		print STDERR Dumper $doc
	}

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
                    # On retrouve l'evenement de la r gle
                    my $class = $event->{scheduler_event_class}->{value};
                    # ID multiples (modif Klaus)
                    if ($event->{scheduler_event_id}) {
                        foreach my $id (split(';',$event->{scheduler_event_id}->{value})) {
                            # On recherche la classe et l'id
                            if ($EventRule{"$class|$id"}) {
                                my $action = $EventRule{"$class|$id"};
                                # pour le positionner
                                print '"EVENT//'.$action.'"'."\n";
				PrintLink( $jobname, 'EVENT//'.$action, '[style=dashed,color=black]' );
                                $ShowEvent{$action}++;
                            }
                        }
                    }
                }
	}
		
	if ($doc->{job}->{process_class}) {
		$label .= TableLine(0, 'process_class', $doc->{job}->{process_class})."\n";
		# Un lien de la definition vers le job
		my $process_class = CleanFilename2(Basename("$f"),$doc->{job}->{process_class}).'.process_class.xml';
		PrintLink( $jobname, $process_class, '[color=cyan,style=dotted]' );
		ReadProcessClass( $process_class );
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
					PrintLink( $jobname, 'INCLUDE/'.Basename($f).'/'.$monitor->{script}->{include}->{live_file}, '[dir=back,color=grey]', $unicID);				
					# Ouverture du script ?
					GetInclude( $monitor->{script}->{include}->{live_file},Basename($f) );
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
				PrintLink( $jobname, 'LOCK//'.CleanFilename(Basename( $f ).'/'.$lock->{lock}), '[color=yellow,style=dashed]', $unicID );
			}
			else {
				$label .= TableLine(0,  'lock_open', $lock->{lock} );		
				PrintLink( $jobname, 'LOCK//'.CleanFilename(Basename( $f ).'/'.$lock->{lock}), '[color=yellow,style=dotted]', $unicID );
			}
		}
	}

	# On traite la s?rie de commandes
	if ($doc->{job}->{commands}) {
		my @COMMANDS = GetRefArray( $doc->{job}->{commands} );
		foreach my $command ( @COMMANDS ) {
			my $type = $command->{on_exit_code};
			if ($command->{start_job}) {
				my @STARTS = GetRefArray( $command->{start_job} );
				foreach my $start ( @STARTS ) {
					if ($start->{job}) {
						my $next = 'draft/'.$start->{job}.'.job.xml';
						ReadJob($next);
						if (my $at = $start->{at}) {
							$label .= TableLine(0,  'start_job', $next, $at);	
						}
						else {
							$label .= TableLine(0,  'start_job', $next);	
						}
						if ($type eq 'success') {
							PrintLink( $jobname, $next, '[color=green]', $unicID); 
						}
						elsif ($type eq 'error') {
							PrintLink( $jobname, $next, '[color=red]',$unicID ); 
						}
						elsif ($type =~ /^SIG/) {
							PrintLink( $jobname,$next, '[label='.$type.',color=purple]', $unicID ); 
						}
						else {
							PrintLink( $jobname, $next, '[label='.$type.',color=orange]', $unicID ); 
						}
						$label .= GetParams($start->{params});
					}
				}
			}
			if ( $command->{order} ) {
				# Departs de chaines
                                        my @ORDERS = GetRefArray( $command->{order} );
					# my @ORDERS = ();
					#if ( ref ( $command->{order} ) eq 'HASH' ) {
                                            # Ordre direct ou non ?
                                        #    if ($command->{order}->{id}) {
                                        #        push( @ORDERS, $command->{order} );
                                        #    }
                                        #    else {
					#	foreach my $k ( keys %{ $command->{order} } ) {
					#		push( @ORDERS, $command->{order}->{$k} );
					#	}
                                        #    }
					#}
					#else {
					#	@ORDERS = GetRefArray( $command->{order} );
					#}
					
					foreach my $start ( @ORDERS ) {
						if ($start->{job_chain}) {
                                                        # Si il y a un state, on part sur le prochain état
                                                        my $next;
                                                        if ($start->{state}) {
                                                            $next = $start->{state};
                                                        }
                                                        else {
                                                            $next = 'HEAD';
                                                        }
                                                        $next = CleanFilename($start->{job_chain}.'/'.$next);    
							$next =~ s/^\///;
							if (my $at = $start->{at}) {
								$label .= TableLine(0,  'order_chain', $start->{job_chain}, $at);	
							}
							else {
								$label .= TableLine(0,  'order_chain', $start->{job_chain});	
							}
							if ($type eq 'success') {
                                                                push(@LINKS, CalcLink( $jobname, $next, '[color=green]', $unicID ) ); 
							}
							elsif ($type eq 'error') {
								push(@LINKS, CalcLink( $jobname, $next, '[color=red]', $unicID ) ); 
							}
							elsif ($type =~ /^SIG/) {
								push(@LINKS, CalcLink( $jobname, $next, '[label='.$type.',color=purple]' ) ); 
							}
							else {
								push(@LINKS, CalcLink( $jobname, $next, '[label='.$type.',color=orange]' ) ); 
							}
							$label .= GetParams($start->{params});

                                                        # On va chercher la chaine
                                                        push(@CHAINS, $start->{job_chain});
						}
					}
			}
		}
	}
	$label .= '</TABLE>';
	PrintNode( $jobname, '[label=<'.$label.'>]' );		
	push(@JOBS,$jobname);
        
        # Un evenement le pousse ?
        if ($StartJob{$jobname}) {
            my $action = $StartJob{$jobname};
            # pour le positionner
            print '"EVENT//'.$action.'"'."\n";
            print '"EVENT//'.$action.'" -> "'.$jobname.'" [style=dashed,color=black]';
            $ShowEvent{$action}++;
        }
}

# ---------------------------------------------
# ReadConfig()
# ---------------------------------------------
sub ReadConfig {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );

	$file = $f;
	$f =~ s/\.config\.xml$//;

	my $config = CleanChainLabel($f);
	if (! -f "$objdir/$file") {
            # un fichier de config est obligatoire ?
            # print "\"".$file."\" [shape=ellipse,color=red]\n";
            return;
	}
	my $doc = $parser->XMLin("$objdir/$file");
	if ($debug =~ /job/) {
		print STDERR "$objdir/$file\n";
		print STDERR Dumper $doc
	}

	# Chaine ou Chaine,Order ?
	my($chain,$order) = split(',',$config);
	if ($order) {
		# On ajoute un lien de la config � la chaine
		PrintLink( 'CFG//'.$config, $chain.'/HEAD', '[style=dotted,color=purple]');
		# Si l'ordre est la, on ajoute un lien de l'ordre a la config
		PrintLink( CleanFilename("$hotfolder/$order/$chain"), 'CFG//'.$config, '[style=dotted,color=purple]');
	}
	else {
		# Si l'ordre est l�, on ajoute un lien de l'ordre � la config
		PrintLink( CleanFilename($f.'/HEAD'), 'CFG//'.$config, '[style=dotted,color=purple]' );
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
						PrintLink( 'CFG//'.$config, CleanFilename($f.'/'.$state), '[style=dotted,color=purple]', $unicID );
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
# ReadSchedule()
# ---------------------------------------------
sub ReadSchedule {
my($f) = @_;

	# on teste la presence du fichier 
	$file = $f;
	$f =~ s/\.schedule\.xml$//;
	my($schedule) = CleanChainLabel($f);
	if (! -f "$objdir/$file") {
		print "\"$file\" [shape=ellipse,color=red]\n";
		return;
	}

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$objdir/$file");
	print STDERR Dumper $doc
		if ($debug =~ /schedule/);

	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="blue" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'schedule', $schedule );
	# Substitution
	if ($doc->{schedule}->{substitute}) {
		my $substitute = $doc->{schedule}->{substitute};
		$label .= TableLine(1,  'substitute', $substitute );
		print '"'.$schedule.'.schedule.xml" -> "'.$substitute.'.schedule.xml" [color=blue,style=dashed]';
	}
	foreach my $k ('title', 'valid_to', 'valid_from' ) { 
		$label .= TableLine(1, $k, Encode($doc->{schedule}->{$k}) )
			if ($doc->{schedule}->{$k});
	}
	$label .= GetSchedule( $doc->{schedule} );
	
	$label .= '</TABLE>';
	PrintNode( $schedule.".schedule.xml", '[label=<'.$label.'>]' );		
}
# ---------------------------------------------
# ReadHoliday()
# ---------------------------------------------
sub ReadHoliday {
my($f) = @_;

	# on teste la presence du fichier 
	$file = $f;
	$f =~ s/\.holiday\.xml$//;
	my($holiday) = CleanChainLabel($f);
	if (! -f "$objdir/$file") {
		print "\"$file\" [shape=ellipse,color=red]\n";
		return;
	}

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$objdir/$file");
	print STDERR Dumper $doc;
	my($label) = '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0" COLOR="blue" BGCOLOR="white">'."\n";
	$label .= TableLine(0,  'holiday', $holiday );
	# Substitution
	if ($doc->{holiday}->{date}) {
		$label .= TableLine(1,  'date', $doc->{holiday}->{date});
	}
	if ($doc->{holiday}) {
		$label .= GetScheduleDates($doc->{holiday});
	}
	$label .= '</TABLE>';
	PrintNode( $file, '[label=<'.$label.'>]' );		
	
}

# ---------------------------------------------
# GetSchedule()
# ---------------------------------------------
sub GetSchedule {		
my($schedule,$from)=@_;
my($label)='';

		# Inclusion
		if ($schedule->{schedule}) {
			$label .= TableLine(0, 'schedule', $schedule->{schedule});
			my $sched = CleanFilename($schedule->{schedule});
			PrintLink( $sched.'.schedule.xml' , $from, '[color=blue,style=dashed]', $unicID );
			
			# Cas ou on vient d'un autre objet et qu'aucun repertoire n'est indiqu�
			my($path) = '';
			if (($from) and ($sched =~ /^[a-z0-9]/i)) {
				$path = dirname($from).'/';
				print STDERR "(($path))";
			}
			ReadSchedule($path.$sched.".schedule.xml");
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
					$label .= TableLine(0, 'include', $inc );
					$from = CleanFilename( $from );
					$inc =  CleanFilename2( dirname($from ), $inc );
					PrintLink(  $inc, $from , '[color=blue,style=dashed]');
					ReadHoliday( $inc );
				}
				else {
					print "TODO!!";
				}
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
		if ($hd->{date}) {
			$label .= TableLine($tab, 'date', $hd->{date});
		}
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
# ReadProcessClass()
# ---------------------------------------------
sub ReadProcessClass {
my($f) = @_;

	return if ($Done{$f});
	$Done{$f}++;

        if (! -f "$objdir/$f") {
           # print "\"$objdir/$f\" [color=red]\n";
            return;
        }
	
	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$objdir/$f");
	print STDERR Dumper $doc
		if ($debug =~ /process_class/);

	$file = $f;	
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
	PrintNode( $file, '[label=<'.$label.'>]' );		
}

# ---------------------------------------------
# ReadLock()
# ---------------------------------------------
sub ReadLock {
my($f) = @_;

	my $parser = XML::Simple->new( KeepRoot => 1 );
	my $doc = $parser->XMLin("$objdir/$f");
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
	PrintNode( 'LOCK//'.CleanFilename(Basename( $f ).'/'.$lock), '[label=<'.$label.'>]' );		
}

# ---------------------------------------------
# GetInclude()
# ---------------------------------------------
sub GetInclude {
my($file,$folder)=@_;
return "$folder/$file";
	if (open(FILE,"$objdir/$folder/$file")) {
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
return CheckString($txt);
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
		return CheckString($txt);
	}
return CheckString($txt);
}

sub CleanFilename {
my($t) = @_;
	$t =~ s/^\.//;
	$t =~ s/^\///;
		
return CheckString($t);
}

sub CleanFilename2 {
my($base,$file ) = @_;
	# Si on est relatif 
	if ($file =~ /^\.\.\//) {
		my @Base = split('/',$base);
		while ($file =~ s/^\.\.\///) {
			pop (@Base);
		}
		$base = join('/',@Base);
	}  
	elsif ($file =~ /^\//) { # Si on est en absolu
		# Pas de souci
	} 
	else {
		# on se positionne sur un live ?
		# $base =~ s/^(draft)\///;
	}
	my $t = $base.'/'.$file;
	$t =~ s/^\.//;
	$t =~ s/^\///;
		
return $t;
}

sub CleanLabel {
my($t) = @_;
	$t =~ s/^\.\///g;
return CheckString($t);		
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
return CheckString($t);
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
sub GetRefArray2 {
my($ref)=@_;
	if (ref( $ref ) eq 'ARRAY' ) {
		return @{ $ref } ;
	}
	elsif (ref( $ref ) eq 'HASH') {
		# on cree un tableau
		my @New = ();
		foreach my $k ( %{ $ref } ) {
			if ($ref->{$k}) {
				push(@New, $k );
				push(@New, $ref->{$k});
				print STDERR "(($k)) ".$ref->{$k}."\n";
			}
		}
		return  @New ;
	}
return ( $ref );
}

sub trim {
  my ($string) = @_;
  if (!$string) { return $string; }
  $string =~ s/^\s+//;
  $string =~ s/\s+$//;
  return $string;
}

sub PrintNode {
my($source, $style) = @_;
	my $node = '"'.CheckString($source).'"';
	$node .= ' '.$style."\n" if $style;
	return if $Done{$node};
	$Done{$node}++;
	print $node;
}

sub CalcLink {
my($source,$target,$style, $unicID) = @_;
	my $link;
	if ($unicID) {
#		$link = '"'.CheckString($source).'":'.$unicID;
		$link = '"'.CheckString($source).'"';
	}
	else {
		$link = '"'.CheckString($source).'"';
	}
	$link .= ' -> "'.CheckString($target).'"'." $style\n";
	return if $Done{$link};
	$Done{$link}++;
return $link;
}
sub PrintLink {
    print CalcLink(@_);
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
my @fr = ('','Janvier','F vrier','Mars','Avril','Mai','Juin','Juillet','Ao t','Septembre','Octobre','Novembre','D cembre');
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
			push(@ND, $ultimos.$d.'?me');
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
		return 'Deuxi?me '.$fr[$t];
	}
	elsif  ($which eq '3') {
		return 'Troisi?me '.$fr[$t];
	}
	elsif  ($which eq '4') {
		return 'Quatri?me '.$fr[$t];
	}
	elsif  ($which eq '5') {
		return 'Cinqui?me '.$fr[$t]; # n'existe pas mais ce n'est pas normal
	}
	elsif  ($which eq '-1') {
		return 'Dernier '.$fr[$t];
	}
	elsif ($which eq '-2') {
		return 'Avant-dernier '.$fr[$t];
	}
	elsif ($which eq '-3') {
		return 'Ante-penulti?me '.$fr[$t];
	}
	elsif ($which eq '-4') {
		return 'Avant ante-penulti?me '.$fr[$t];
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
return CheckString($str);
}

# ---------------------------------------------
# LongString
# ---------------------------------------------

sub LongString {
my($str) = @_;
    $str =~ s/([\.\;\,])(\w)/$1\n$2/g;
return CheckString($str);
}

sub NoPassword {
my($str) = @_;
    $str .= " ";
    $str =~ s/password=(.*?) /password=**********/g;
return $str;
}

sub CheckString {
my($str) = @_;
#    $str =~ s/[^a-zA-Z0-9\s\-_.,]/\?/g;
return encode('utf8', decode('iso-8859-1', $str) );
}

